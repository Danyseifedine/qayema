<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderCategoriesRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Models\Restaurant;
use App\Services\Menu\DisplayOrder;
use App\Services\Menu\MenuLanguages;
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
    use ResolvesRestaurant;

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Category::class);
        $restaurant = $this->restaurant($request);

        $categories = $restaurant->categories()
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
                'display_order' => (int) $locked->categories()->max('display_order') + 1,
            ]);
            $languages = $locked->menuLanguages();
            MenuLanguages::fill($category, 'name', MenuLanguages::input($request, 'name', $languages), $languages);
            MenuLanguages::fill($category, 'description', MenuLanguages::input($request, 'description', $languages), $languages);
            $category->restaurant()->associate($locked);
            $category->save();

            return $category;
        });

        return (new CategoryResource($category->loadCount('dishes')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Category $category): CategoryResource
    {
        $this->authorize('view', $category);

        return new CategoryResource($category->loadCount('dishes'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);

        // Only the menu's current languages are written. Text in a language
        // the owner has switched away from stays, hidden, for if they switch
        // back.
        $languages = $category->restaurant->menuLanguages();
        MenuLanguages::fill($category, 'name', MenuLanguages::input($request, 'name', $languages), $languages);
        MenuLanguages::fill($category, 'description', MenuLanguages::input($request, 'description', $languages), $languages);

        $category->save();

        return new CategoryResource($category->loadCount('dishes'));
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
        $owned = $restaurant->categories()->whereIn('id', $ids)->pluck('id')->flip();

        // Keep the client's order, drop anything it does not own. `flip()` makes
        // that check a hash lookup rather than a scan per id.
        $ordered = array_values(array_filter($ids, static fn (int $id): bool => $owned->has($id)));

        DisplayOrder::apply($restaurant->categories(), $ordered);

        $categories = $restaurant->categories()
            ->withCount('dishes')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }
}
