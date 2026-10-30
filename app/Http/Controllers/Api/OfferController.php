<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Enums\MenuEventType;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderOffersRequest;
use App\Http\Requests\StoreOfferRequest;
use App\Http\Requests\UpdateOfferActiveRequest;
use App\Http\Requests\UpdateOfferRequest;
use App\Http\Resources\OfferResource;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Services\Media\MediaService;
use App\Services\Menu\DisplayOrder;
use App\Services\Menu\MenuLanguages;
use App\Services\Offers\DealDish;
use App\Services\Offers\OfferSlots;
use App\Support\Price;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The owner's offers (the `offers` flag, Premium): list, create, edit,
 * pause, reorder and delete. Scoped to the owner's restaurant and
 * authorized through OfferPolicy. An offer's own dish (its deal, DealDish)
 * is written with it, in the same transaction.
 */
class OfferController extends Controller
{
    use ResolvesRestaurant;

    /** How far back the shown and tap counts go. */
    public const STATS_DAYS = 30;

    /** What OfferResource reads, loaded with the list so nothing is a query per offer. */
    private const WITH = ['media', 'dishes:id', 'deal.media'];

    public function __construct(
        private readonly MediaService $media,
        private readonly DealDish $deals,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Offer::class);
        $restaurant = $this->offersRestaurant($request, writing: false);
        $offers = $this->listOf($restaurant);

        return OfferResource::collection($offers)->additional([
            'meta' => [
                'limit' => Offer::MAX_PER_RESTAURANT,
                'timezone' => $restaurant->localTimezone(),
                'currency' => $restaurant->currency,
                // An amount off is in the main currency; a dish in the second one takes it at the rate.
                'second_currency' => $restaurant->second_currency,
                'exchange_rate' => $restaurant->exchange_rate === null ? null : Price::rate((string) $restaurant->exchange_rate),
                // Each place's offers switched on, against the package's limit.
                'slots' => OfferSlots::of($restaurant, $offers),
                // An object even when empty, so it reads as a map.
                'stats' => (object) $this->stats($restaurant),
            ],
        ]);
    }

    public function store(StoreOfferRequest $request): JsonResponse
    {
        $this->authorize('create', Offer::class);
        $restaurant = $this->offersRestaurant($request);

        [$offer, $deal] = DB::transaction(function () use ($request, $restaurant): array {
            $locked = $this->locked($restaurant);

            if ($locked->offers()->count() >= Offer::MAX_PER_RESTAURANT) {
                throw ValidationException::withMessages([
                    'title' => __('A menu can have up to :limit offers. Delete one you no longer use.', ['limit' => Offer::MAX_PER_RESTAURANT]),
                ]);
            }

            $offer = new Offer(['display_order' => (int) $locked->offers()->max('display_order') + 1]);
            $offer->restaurant()->associate($locked);

            return [$offer, $this->fill($offer, $request, $locked)];
        });

        $this->syncImages($request, $offer, $deal);

        return (new OfferResource($this->loaded($offer, $restaurant)))->response()->setStatusCode(201);
    }

    public function update(UpdateOfferRequest $request, Offer $offer): OfferResource
    {
        $this->authorize('update', $offer);
        $restaurant = $this->offersRestaurant($request);

        $deal = DB::transaction(fn (): ?array => $this->fill($offer, $request, $this->locked($restaurant)));
        $this->syncImages($request, $offer, $deal);

        return new OfferResource($this->loaded($offer, $restaurant));
    }

    /** Pause or resume, the edit made most. */
    public function updateActive(UpdateOfferActiveRequest $request, Offer $offer): OfferResource
    {
        $this->authorize('update', $offer);
        $restaurant = $this->offersRestaurant($request);

        DB::transaction(function () use ($request, $offer, $restaurant): void {
            $offer->is_active = $request->boolean('is_active');
            OfferSlots::ensureRoomFor($offer, $this->locked($restaurant), 'is_active');
            $offer->save();
        });

        return new OfferResource($this->loaded($offer, $restaurant));
    }

    public function destroy(Request $request, Offer $offer): JsonResponse
    {
        $this->authorize('delete', $offer);
        $this->offersRestaurant($request);

        $offer->delete();

        return response()->json(null, 204);
    }

    /** A new order, touching only ids the restaurant owns. */
    public function reorder(ReorderOffersRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Offer::class);
        $restaurant = $this->offersRestaurant($request);

        /** @var array<int, int> $ids */
        $ids = $request->validated('ids');
        DisplayOrder::apply($restaurant->offers(), $ids);

        return OfferResource::collection($this->listOf($restaurant));
    }

    /**
     * The restaurant read again under its row lock, so two saves at once
     * cannot both take the last slot or the last offer.
     */
    private function locked(Restaurant $restaurant): Restaurant
    {
        return Restaurant::query()->whereKey($restaurant->id)->lockForUpdate()->firstOrFail();
    }

    /**
     * The owner's restaurant, refused (403) while its package has no offers.
     * A change is refused too while the menu is connected to a POS, which
     * keeps offers off (the saved ones can still be read).
     */
    private function offersRestaurant(Request $request, bool $writing = true): Restaurant
    {
        $restaurant = $this->restaurant($request);

        abort_unless($restaurant->entitlements()->can(Feature::Offers), 403, __('Offers are not on your package.'));

        if ($writing && $restaurant->isConnectedToPos()) {
            throw ValidationException::withMessages(['pos' => __('Offers are off while your menu is connected to a POS.')]);
        }

        return $restaurant;
    }

    /**
     * Everything the form sent, written over the offer; its deal dish made,
     * edited or deleted as sent; and its dishes set to the list sent, or to
     * its deal dish alone while it has one.
     *
     * @return array{0: Dish, 1: bool}|null the deal dish written now and whether it was made now
     */
    private function fill(Offer $offer, StoreOfferRequest $request, Restaurant $restaurant): ?array
    {
        $languages = $restaurant->menuLanguages();
        MenuLanguages::fill($offer, 'title', MenuLanguages::input($request, 'title', $languages), $languages);
        MenuLanguages::fill($offer, 'description', MenuLanguages::input($request, 'description', $languages), $languages);

        $discount = (string) $request->validated('discount_type');
        $windows = $request->validated('time_windows') ?: null;

        $offer->fill([
            'show_text' => $request->showsText(),
            'placements' => array_values($request->validated('placements')),
            'banner_shape' => $request->validated('banner_shape') ?? 'rectangle',
            'popup_shape' => $request->validated('popup_shape') ?? 'square',
            'action' => $request->validated('action'),
            'action_url' => $request->validated('action') === 'link' ? $request->validated('action_url') : null,
            'show_again' => $request->validated('show_again'),
            'delay_seconds' => (int) $request->validated('delay_seconds'),
            'ad_delay_seconds' => (int) ($request->validated('ad_delay_seconds') ?? 2),
            'ad_seconds' => (int) $request->validated('ad_seconds'),
            'discount_type' => $discount,
            'discount_value' => $discount === 'none' ? null : $request->validated('discount_value'),
            'starts_at' => $request->localDate('starts_at'),
            'ends_at' => $request->localDate('ends_at'),
            'repeat' => $request->validated('repeat'),
            'weekdays' => $request->validated('repeat') === 'weekly' ? array_values((array) $request->validated('weekdays')) : null,
            'month_days' => $request->validated('repeat') === 'monthly' ? array_map('intval', array_values((array) $request->validated('month_days'))) : null,
            'time_windows' => $windows === null ? null : array_map(fn (array $window): array => ['from' => $window['from'], 'to' => $window['to']], array_values($windows)),
            // Shown outside its hours only when it has hours to be outside of.
            'shows_outside_hours' => $request->boolean('shows_outside_hours') && ($request->validated('repeat') !== 'always' || $windows !== null),
        ]);

        if ($request->has('is_active')) {
            $offer->is_active = $request->boolean('is_active');
        }

        // A full place refuses it before anything is written.
        OfferSlots::ensureRoomFor($offer, $restaurant, 'placements');
        $offer->save();

        $dropped = $request->dropsDeal() ? $this->deals->drop($offer) : null;

        $saved = $request->sendsDeal() ? $this->deals->save($offer, (array) $request->validated('deal'), $restaurant) : null;
        // Left out of the request, a deal it has stays, and stays its one dish.
        $dealId = match (true) {
            $saved !== null => $saved[0]->id,
            ! $request->exists('deal') && ! $offer->wasRecentlyCreated => $offer->deal()->value('id'),
            default => null,
        };

        // A deal deleted now may still be among the dishes the editor sent.
        $chosen = array_values(array_diff(array_map('intval', (array) $request->validated('dish_ids', [])), [$dropped]));
        $offer->dishes()->sync($dealId !== null ? [(int) $dealId] : $chosen);

        return $saved;
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Offer> */
    private function listOf(Restaurant $restaurant): \Illuminate\Database\Eloquent\Collection
    {
        return $restaurant->offers()
            ->with(self::WITH)
            ->get()
            ->each(fn (Offer $offer) => $offer->setRelation('restaurant', $restaurant));
    }

    private function loaded(Offer $offer, Restaurant $restaurant): Offer
    {
        return $offer->load(self::WITH)->setRelation('restaurant', $restaurant);
    }

    /**
     * How often each offer was shown and tapped over the last 30 days, in
     * one grouped query: offer id => {shown, taps}.
     *
     * @return array<string, array{shown: int, taps: int}>
     */
    private function stats(Restaurant $restaurant): array
    {
        $stats = [];

        $rows = $restaurant->menuEvents()
            ->toBase()
            ->whereIn('type', [MenuEventType::OfferShown->value, MenuEventType::OfferTap->value])
            ->where('occurred_at', '>=', now()->subDays(self::STATS_DAYS))
            ->selectRaw('type, value, COUNT(*) as total')
            ->groupBy('type', 'value')
            ->get();

        foreach ($rows as $row) {
            $id = (string) $row->value;
            $stats[$id] ??= ['shown' => 0, 'taps' => 0];
            $stats[$id][$row->type === MenuEventType::OfferShown->value ? 'shown' : 'taps'] += (int) $row->total;
        }

        return $stats;
    }

    /**
     * The picture (a new upload, or removed; untouched otherwise), then the
     * photo of the deal dish written in this save, which may copy it.
     *
     * @param  array{0: Dish, 1: bool}|null  $deal  the deal dish written now and whether it was made now
     */
    private function syncImages(StoreOfferRequest $request, Offer $offer, ?array $deal): void
    {
        $before = $offer->getFirstMedia('image')?->id;
        $this->media->sync($offer, $request->input('image_key'), $request->boolean('delete_image'), 'image', 'offer');

        if ($deal === null) {
            return;
        }

        $changed = $offer->load('media')->getFirstMedia('image')?->id !== $before;
        $this->deals->syncPhoto($deal[0], $offer, (array) $request->validated('deal'), $deal[1], $changed);
    }
}
