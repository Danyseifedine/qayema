<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderDishesRequest;
use App\Http\Requests\StoreDishRequest;
use App\Http\Requests\UpdateDishRequest;
use App\Http\Resources\DishResource;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Global\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Dish CRUD for the dashboard SPA. Every action is scoped to the authenticated
 * user's restaurant and authorized through DishPolicy.
 */
class DishController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Dish::class);
        $restaurant = $this->restaurant($request);

        $dishes = $restaurant->dishes()
            ->with(['media', 'category'])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return DishResource::collection($dishes)->additional([
            'meta' => [
                'used' => $dishes->count(),
                'limit' => $restaurant->dish_limit,
                'currency' => $restaurant->currency,
            ],
        ]);
    }

    public function store(StoreDishRequest $request): JsonResponse
    {
        $this->authorize('create', Dish::class);
        $restaurant = $this->restaurant($request);

        $dish = DB::transaction(function () use ($request, $restaurant): Dish {
            $locked = Restaurant::query()->whereKey($restaurant->id)->lockForUpdate()->firstOrFail();

            if ($locked->hasReachedDishLimit()) {
                throw ValidationException::withMessages([
                    'name' => __('You have reached your plan limit of :limit dishes.', ['limit' => $locked->dish_limit]),
                ]);
            }

            $dish = new Dish([
                'name' => $this->localeMap($request->validated('name')),
                'ingredients' => $this->localeMap($request->validated('ingredients')),
                'price' => $request->validated('price'),
                'category_id' => $request->validated('category_id'),
                'is_available' => $request->boolean('is_available', true),
                'display_order' => (int) $locked->dishes()->max('display_order') + 1,
            ]);
            $dish->restaurant()->associate($locked);
            $dish->save();

            return $dish;
        });

        $this->syncImage($request, $dish);

        return (new DishResource($dish->load(['media', 'category'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Dish $dish): DishResource
    {
        $this->authorize('view', $dish);

        return new DishResource($dish->load(['media', 'category']));
    }

    public function update(UpdateDishRequest $request, Dish $dish): DishResource
    {
        $this->authorize('update', $dish);

        if ($request->has('name')) {
            $dish->name = $this->localeMap($request->validated('name'));
        }
        if ($request->has('ingredients')) {
            $dish->ingredients = $this->localeMap($request->validated('ingredients'));
        }
        if ($request->has('price')) {
            $dish->price = $request->validated('price');
        }
        if ($request->has('category_id')) {
            $dish->category_id = $request->validated('category_id');
        }
        if ($request->has('is_available')) {
            $dish->is_available = $request->boolean('is_available');
        }
        $dish->save();

        $this->syncImage($request, $dish);

        return new DishResource($dish->load(['media', 'category']));
    }

    public function destroy(Request $request, Dish $dish): JsonResponse
    {
        $this->authorize('delete', $dish);

        $dish->delete();

        return response()->json(null, 204);
    }

    /**
     * Persist a new display order. Only ids the restaurant owns are touched;
     * unknown or foreign ids are silently ignored so a tampered payload can't
     * reorder another restaurant's dishes.
     */
    public function reorder(ReorderDishesRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Dish::class);
        $restaurant = $this->restaurant($request);

        /** @var array<int, int> $ids */
        $ids = $request->validated('ids');
        $owned = $restaurant->dishes()->whereIn('id', $ids)->pluck('id')->all();

        DB::transaction(function () use ($ids, $owned, $restaurant): void {
            $position = 1;

            foreach ($ids as $id) {
                if (in_array($id, $owned, true)) {
                    $restaurant->dishes()->whereKey($id)->update(['display_order' => $position++]);
                }
            }
        });

        $dishes = $restaurant->dishes()
            ->with(['media', 'category'])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return DishResource::collection($dishes);
    }

    private function restaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403);

        return $restaurant;
    }

    /**
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
     * dish's cover collection, or clear it when `delete_image` is set. The raw
     * upload is never stored — MediaService optimized it at temp-upload time.
     * `sync()` clears the (multi-image) collection first, so it stays a single
     * cover.
     */
    private function syncImage(Request $request, Dish $dish): void
    {
        $this->media->sync(
            $dish,
            $request->input('image_key'),
            $request->boolean('delete_image'),
            'images',
            'dish',
        );
    }
}
