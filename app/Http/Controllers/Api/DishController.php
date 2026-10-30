<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\GuardsPosMenu;
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
use App\Services\Menu\DishBuildsSync;
use App\Services\Menu\DishOptionsSync;
use App\Services\Menu\DisplayOrder;
use App\Services\Menu\MenuLanguages;
use App\Support\Price;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Dish CRUD for the dashboard SPA. Every action is scoped to the authenticated
 * user's restaurant and authorized through DishPolicy.
 */
class DishController extends Controller
{
    use GuardsPosMenu, ResolvesRestaurant;

    /** What DishResource reads, loaded up front so a list is not one query per dish. */
    private const WITH = ['media', 'variants.options', 'addons', 'addonGroups', 'builds.choices', 'offer'];

    public function __construct(
        private readonly MediaService $media,
        private readonly DishOptionsSync $options,
        private readonly DishBuildsSync $builds,
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
                'limit' => $restaurant->limitOf(Feature::DishLimit),
                'currency' => $restaurant->currency,
                // The one some dishes may be priced in instead, and its rate.
                'second_currency' => $restaurant->second_currency,
                'exchange_rate' => $restaurant->exchange_rate === null ? null : Price::rate((string) $restaurant->exchange_rate),
            ],
        ]);
    }

    public function store(StoreDishRequest $request): JsonResponse
    {
        $this->authorize('create', Dish::class);
        $restaurant = $this->restaurant($request);
        $this->refuseWhilePosConnected($restaurant);

        $dish = DB::transaction(function () use ($request, $restaurant): Dish {
            $locked = Restaurant::query()->whereKey($restaurant->id)->lockForUpdate()->firstOrFail();

            $locked->ensureRoomFor(
                Feature::DishLimit,
                'name',
                'You have reached your plan limit of :limit dishes.',
                'You have reached the fair-use limit of :limit dishes. Contact us if you need more.',
            );
            // A dish guests build counts against its own limit too.
            if (filled($request->validated('builds'))) {
                $locked->ensureRoomFor(Feature::BuildLimit, 'builds', 'You can have up to :limit dishes guests build. Delete one to add another.');
            }

            $dish = new Dish([
                'price' => $request->validated('price'),
                'currency' => $locked->ownCurrency($request->validated('currency')),
                'category_id' => $request->validated('category_id'),
                'icon' => $request->validated('icon'),
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

        // Connected to a POS: everything but the icon is the POS's.
        if ($dish->restaurant->isConnectedToPos()) {
            if ($request->has('icon')) {
                $dish->icon = $request->validated('icon');
                $dish->save();
            }

            return new DishResource($dish->load(self::WITH));
        }

        // Only the menu's current languages are written; hidden ones stay.
        $languages = $dish->restaurant->menuLanguages();
        MenuLanguages::fill($dish, 'name', MenuLanguages::input($request, 'name', $languages), $languages);
        MenuLanguages::fill($dish, 'ingredients', MenuLanguages::input($request, 'ingredients', $languages), $languages);
        if ($request->has('price')) {
            $dish->price = $request->validated('price');
        }
        if ($request->has('currency')) {
            $dish->currency = $dish->restaurant->ownCurrency($request->validated('currency'));
        }
        if ($request->has('category_id')) {
            $dish->category_id = $request->validated('category_id');
        }
        if ($request->has('icon')) {
            $dish->icon = $request->validated('icon');
        }
        if ($request->has('is_available')) {
            $dish->is_available = $request->boolean('is_available');
        }

        DB::transaction(function () use ($request, $dish, $languages): void {
            // A dish without builds given some becomes one guests build.
            if (filled($request->validated('builds')) && ! $dish->builds()->exists()) {
                Restaurant::query()->whereKey($dish->restaurant_id)->lockForUpdate()->firstOrFail()
                    ->ensureRoomFor(Feature::BuildLimit, 'builds', 'You can have up to :limit dishes guests build. Delete one to add another.');
            }
            $dish->save();
            $this->syncOptions($request, $dish, $languages);
        });

        $this->syncImage($request, $dish);

        return new DishResource($dish->load(self::WITH));
    }

    public function destroy(Dish $dish): JsonResponse
    {
        $this->authorize('delete', $dish);
        $this->refuseWhilePosConnected($dish->restaurant);

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

        DisplayOrder::apply($restaurant->dishes(), $ids);

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
        $this->refuseWhilePosConnected($dish->restaurant);

        $dish->update(['is_available' => $request->boolean('is_available')]);

        return new DishResource($dish->load(self::WITH));
    }

    /**
     * The variants, add-ons and builds the form sent. A list the request
     * leaves out (not sent, or not on: see ValidatesDishOptions and
     * ValidatesDishBuilds) stays as it is.
     *
     * @param  array<int, string>  $languages
     */
    private function syncOptions(StoreDishRequest|UpdateDishRequest $request, Dish $dish, array $languages): void
    {
        $this->options->sync($dish, $request->validated('variants'), $request->validated('addons'), $languages);
        $this->builds->sync($dish, $request->validated('builds'), $languages);
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
