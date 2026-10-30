<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\GuardsPosMenu;
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

/**
 * Category CRUD + reordering for the dashboard SPA. Every action is scoped to
 * the authenticated user's restaurant and authorized through CategoryPolicy.
 */
class CategoryController extends Controller
{
    use GuardsPosMenu, ResolvesRestaurant;

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
                'limit' => $restaurant->limitOf(Feature::CategoryLimit),
            ],
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $this->authorize('create', Category::class);
        $restaurant = $this->restaurant($request);
        $this->refuseWhilePosConnected($restaurant);

        // Re-check the plan limit and insert under a row lock so two concurrent
        // creates can't both slip past the cap (time-of-check/time-of-use).
        $category = DB::transaction(function () use ($request, $restaurant): Category {
            $locked = Restaurant::query()->whereKey($restaurant->id)->lockForUpdate()->firstOrFail();

            $locked->ensureRoomFor(
                Feature::CategoryLimit,
                'name',
                'You have reached your plan limit of :limit categories.',
                'You have reached the fair-use limit of :limit categories. Contact us if you need more.',
            );

            $category = new Category([
                'icon' => $request->validated('icon'),
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

    public function update(UpdateCategoryRequest $request, Category $category): CategoryResource
    {
        $this->authorize('update', $category);

        // Connected to a POS: its name and description are the POS's; only
        // the icon is the owner's.
        if ($category->restaurant->isConnectedToPos()) {
            if ($request->has('icon')) {
                $category->icon = $request->validated('icon');
                $category->save();
            }

            return new CategoryResource($category->loadCount('dishes'));
        }

        // Only the menu's current languages are written. Text in a language
        // the owner has switched away from stays, hidden, for if they switch
        // back.
        $languages = $category->restaurant->menuLanguages();
        MenuLanguages::fill($category, 'name', MenuLanguages::input($request, 'name', $languages), $languages);
        MenuLanguages::fill($category, 'description', MenuLanguages::input($request, 'description', $languages), $languages);
        // A client that does not send it keeps the icon there is.
        if ($request->has('icon')) {
            $category->icon = $request->validated('icon');
        }

        $category->save();

        return new CategoryResource($category->loadCount('dishes'));
    }

    public function destroy(Category $category): JsonResponse
    {
        $this->authorize('delete', $category);
        $this->refuseWhilePosConnected($category->restaurant);

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

        DisplayOrder::apply($restaurant->categories(), $ids);

        $categories = $restaurant->categories()
            ->withCount('dishes')
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return CategoryResource::collection($categories);
    }
}
