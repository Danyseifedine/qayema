<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderDishesRequest;
use App\Http\Requests\StoreDishRequest;
use App\Http\Requests\UpdateDishAvailabilityRequest;
use App\Http\Requests\UpdateDishRequest;
use App\Http\Resources\DishResource;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Media\MediaService;
use App\Services\Menu\DishOptionsSync;
use App\Services\Menu\DisplayOrder;
use App\Services\Menu\MenuLanguages;
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
    use ResolvesRestaurant;

    /** What DishResource reads, loaded up front so a list is not one query per dish. */
    private const WITH = ['media', 'variants.options', 'addons'];

    public function __construct(
        private readonly MediaService $media,
        private readonly DishOptionsSync $options,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Dish::class);
        $restaurant = $this->restaurant($request);

        $dishes = $restaurant->dishes()
            ->with(self::WITH)
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
                    'name' => $locked->entitlements()->isFairUse(Feature::DishLimit)
                        ? __('You have reached the fair-use limit of :limit dishes. Contact us if you need more.', ['limit' => number_format($locked->dish_limit)])
                        : __('You have reached your plan limit of :limit dishes.', ['limit' => $locked->dish_limit]),
                ]);
            }

            $dish = new Dish([
                'price' => $request->validated('price'),
                'category_id' => $request->validated('category_id'),
                'is_available' => $request->boolean('is_available', true),
                'display_order' => (int) $locked->dishes()->max('display_order') + 1,
            ]);
            $languages = $locked->menuLanguages();
            MenuLanguages::fill($dish, 'name', MenuLanguages::input($request, 'name', $languages), $languages);
            MenuLanguages::fill($dish, 'ingredients', MenuLanguages::input($request, 'ingredients', $languages), $languages);
            $dish->restaurant()->associate($locked);
            $dish->save();
            $this->syncOptions($request, $dish, $languages);

            return $dish;
        });

        $this->syncImage($request, $dish);

        return (new DishResource($dish->load(self::WITH)))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateDishRequest $request, Dish $dish): DishResource
    {
        $this->authorize('update', $dish);

        // Only the menu's current languages are written; hidden ones stay.
        $languages = $dish->restaurant->menuLanguages();
        MenuLanguages::fill($dish, 'name', MenuLanguages::input($request, 'name', $languages), $languages);
        MenuLanguages::fill($dish, 'ingredients', MenuLanguages::input($request, 'ingredients', $languages), $languages);
        if ($request->has('price')) {
            $dish->price = $request->validated('price');
        }
        if ($request->has('category_id')) {
            $dish->category_id = $request->validated('category_id');
        }
        if ($request->has('is_available')) {
            $dish->is_available = $request->boolean('is_available');
        }

        DB::transaction(function () use ($request, $dish, $languages): void {
            $dish->save();
            $this->syncOptions($request, $dish, $languages);
        });

        $this->syncImage($request, $dish);

        return new DishResource($dish->load(self::WITH));
    }

    public function destroy(Dish $dish): JsonResponse
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
        $owned = $restaurant->dishes()->whereIn('id', $ids)->pluck('id')->flip();

        // Keep the client's order, drop anything it does not own. `flip()` makes
        // that check a hash lookup rather than a scan per id.
        $ordered = array_values(array_filter($ids, static fn (int $id): bool => $owned->has($id)));

        DisplayOrder::apply($restaurant->dishes(), $ordered);

        $dishes = $restaurant->dishes()
            ->with(self::WITH)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        return DishResource::collection($dishes);
    }

    /**
     * Flip availability alone: the single most frequent edit, so it gets a
     * call that needs nothing but the flag.
     */
    public function updateAvailability(UpdateDishAvailabilityRequest $request, Dish $dish): DishResource
    {
        $this->authorize('update', $dish);

        $dish->update(['is_available' => $request->boolean('is_available')]);

        return new DishResource($dish->load(self::WITH));
    }

    /**
     * The variants and add-ons the form sent. A list the request leaves out
     * (not sent, or switched off: see ValidatesDishOptions) stays as it is.
     *
     * @param  array<int, string>  $languages
     */
    private function syncOptions(StoreDishRequest|UpdateDishRequest $request, Dish $dish, array $languages): void
    {
        $this->options->sync($dish, $request->validated('variants'), $request->validated('addons'), $languages);
    }

    /**
     * Promote the optimized temp upload (referenced by `image_key`) into the
     * dish's image collection, or clear it when `delete_image` is set. The raw
     * upload is never stored; MediaService optimized it at temp-upload time.
     */
    private function syncImage(Request $request, Dish $dish): void
    {
        $this->media->sync(
            $dish,
            $request->input('image_key'),
            $request->boolean('delete_image'),
            'image',
            'dish',
        );
    }
}
