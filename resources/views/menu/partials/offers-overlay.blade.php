{{--
    What offers put over the menu, and the script that runs them all
    (menu-offers.js): a pop-up (native <dialog>, closed by the guest) and an
    ad (a card at the bottom the guest may close once its seconds have
    counted down) per offer placed there, the
    bar that says the menu shows one offer's dishes, and the offers'
    schedules for the phone to judge. Included by menu.partials.scripts only
    when the menu carries offers.
--}}
@use('App\Enums\OfferPlacement')
@use('App\Support\Asset')
@foreach ($offers as $offer)
    @if ($offer->showsIn(OfferPlacement::Popup))
        <dialog class="pop offer-pop" id="offer-pop-{{ $offer->id }}" aria-label="{{ $text($offer, 'title') ?: __('Offers') }}">
            <button type="button" class="dish-sheet-close offer-close" data-offer-close aria-label="{{ __('Close') }}">
                <span class="icon">{!! $icons['close'] !!}</span>
            </button>
            @include('menu.partials.offer-card', ['variant' => 'popup', 'live' => true])
        </dialog>
    @endif
    @if ($offer->showsIn(OfferPlacement::Ad))
        <div class="offer-ad-wrap" id="offer-ad-{{ $offer->id }}" role="status" aria-live="polite" hidden>
            @include('menu.partials.offer-card', ['variant' => 'ad', 'live' => true])
            {{-- The ad cannot be skipped: its seconds count down first (the
                 ring drawn down over --offer-seconds), then the X takes their
                 place and the guest closes it (menu-offers.js). --}}
            <span class="offer-ad-timer" data-offer-timer aria-hidden="true">
                <svg class="offer-ring" viewBox="0 0 36 36"><circle cx="18" cy="18" r="16"/></svg>
                <span class="offer-ad-count" data-offer-count>{{ $offer->ad_seconds }}</span>
            </span>
            <button type="button" class="offer-ad-close" data-offer-close aria-label="{{ __('Close') }}" hidden>
                <span class="icon">{!! $icons['close'] !!}</span>
            </button>
        </div>
    @endif
@endforeach
<script>
    window.QAYEMA_OFFERS = {
        timezone: @js($restaurant->localTimezone()),
        seenKey: @js('qayema-offer-seen-'.$restaurant->slug),
        menu: @js($menu_url),
        preview: @js($offer_preview),
        currency: @js($currency),
        // Each banner dot's name for a screen reader.
        // The tab an offer's dishes open under (menu-nav.js).
        // A pair deal's badge on its dishes ("Buy 2, 2nd 50% off", "Buy 2, 2nd free"), and
        // the toast's title for a tap outside an offer's hours.
        strings: {
            showing: @js(__('Offer: :offer')),
            tab: @js(__('Offers')),
            secondOff: @js(__('Buy 2, 2nd :percent% off')),
            secondFree: @js(__('Buy 2, 2nd free')),
            notNow: @js(__('Not available right now')),
        },
        offers: @js($offers->map(fn ($offer) => [
            'id' => $offer->id,
            'title' => $text($offer, 'title') ?: __('Offers'),
            'placements' => array_map(fn ($placement) => $placement->value, $offer->placementList()),
            'active' => $offer->is_active,
            'schedule' => $offer->schedule($restaurant->localTimezone())->toArray(),
            'discount' => $offer->hasDiscount() ? ['type' => $offer->discount_type->value, 'value' => (float) $offer->discount_value] : null,
            // On the menu all day inside its dates, its deal in its hours only.
            'shown_always' => $offer->shows_outside_hours,
            // When it runs, said to a guest who taps it outside that time.
            'hours' => $offer->schedule($restaurant->localTimezone())->describe($locale),
            'dishes' => $offer->dishes->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            'show_again' => $offer->show_again,
            'delay' => $offer->delay_seconds,
            'ad_delay' => $offer->ad_delay_seconds,
            'seconds' => $offer->ad_seconds,
            'action' => $offer->action,
        ])->values()->all()),
    };
</script>
<script src="{{ Asset::versioned('js/menu-offers.js') }}" defer></script>
