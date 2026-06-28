<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderCategoriesRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Restaurant;
use App\Services\Global\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Category CRUD + reordering for the dashboard SPA. Every action is scoped to
 * the authenticated user's restaurant and authorized through CategoryPolicy.
 */
class CategoryController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Category::class);
        $restaurant = $this->restaurant($request);

        $categories = $restaurant->categories()
            ->with('media')
            ->withCount('dishes')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories)->additional([
            'meta' => [
                'used' => $categories->count(),
                'limit' => $restaurant->category_limit,
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);
        $restaurant = $this->restaurant($request);

        // Re-check the plan limit and insert under a row lock so two concurrent
        // creates can't both slip past the cap (time-of-check/time-of-use).
        $category = DB::transaction(function () use ($request, $restaurant): Category {
            $locked = Restaurant::query()->whereKey($restaurant->id)->lockForUpdate()->firstOrFail();

            if ($locked->hasReachedCategoryLimit()) {
                throw ValidationException::withMessages([
                    'name' => __('You have reached your plan limit of :limit categories.', ['limit' => $locked->category_limit]),
                ]);
            }

            $category = new Category([
                'name' => $this->localeMap($request->validated('name')),
                'description' => $this->localeMap($request->validated('description')),
                'display_order' => (int) $locked->categories()->max('display_order') + 1,
            ]);
            $category->restaurant()->associate($locked);
            $category->save();

            return $category;
        });

        $this->syncImage($request, $category);

        return (new CategoryResource($category->load('media')->loadCount('dishes')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Category $category): CategoryResource
    {
        $this->authorize('view', $category);

        return new CategoryResource($category->load('media')->loadCount('dishes'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);

        // Only replace a translatable field when the client actually sent it —
        // otherwise an empty map would wipe every locale of the omitted field.
        if ($request->has('name')) {
            $category->name = $this->localeMap($request->validated('name'));
        }
        if ($request->has('description')) {
            $category->description = $this->localeMap($request->validated('description'));
        }
        $category->save();

        $this->syncImage($request, $category);

        return new CategoryResource($category->load('media')->loadCount('dishes'));
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $this->authorize('delete', $category);

        $category->delete();

        return response()->json(null, 204);
    }

    /**
     * Persist a new display order. Only ids the restaurant owns are touched;
     * unknown or foreign ids are silently ignored so a tampered payload can't
     * reorder another restaurant's categories.
     */
    public function reorder(ReorderCategoriesRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Category::class);
        $restaurant = $this->restaurant($request);

        /** @var array<int, int> $ids */
        $ids = $request->validated('ids');
        $owned = $restaurant->categories()->whereIn('id', $ids)->pluck('id')->all();

        DB::transaction(function () use ($ids, $owned, $restaurant): void {
            $position = 1;

            foreach ($ids as $id) {
                if (in_array($id, $owned, true)) {
                    $restaurant->categories()->whereKey($id)->update(['display_order' => $position++]);
                }
            }
        });

        $categories = $restaurant->categories()
            ->with('media')
            ->withCount('dishes')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }

    private function restaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403);

        return $restaurant;
    }

    /**
     * Reduce a validated {en, ar} input to the non-empty locales Spatie should
     * store. Empty/absent locales are dropped so they don't overwrite with "".
     *
     * @param  array<string, string|null>|null  $input
     * @return array<string, string>
     */
    private function localeMap(?array $input): array
    {
        $input ??= [];

        return array_filter([
            'en' => trim((string) ($input['en'] ?? '')),
            'ar' => trim((string) ($input['ar'] ?? '')),
        ], static fn (string $value): bool => $value !== '');
    }

    /**
     * Promote the optimized temp upload (referenced by `image_key`) into the
     * category's cover collection, or clear it when `delete_image` is set. The
     * raw upload is never stored — MediaService optimized it at temp-upload time.
     */
    private function syncImage(Request $request, Category $category): void
    {
        $this->media->sync(
            $category,
            $request->input('image_key'),
            $request->boolean('delete_image'),
            'image',
            'category',
        );
    }
}
