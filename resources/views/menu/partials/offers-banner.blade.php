{{--
    The banner carousel under the cover: one card at a time, swiped or
    sliding on by itself, with a dot per card (menu-offers.js draws the
    dots for the cards shown now and slides them). A card shows while its
    offer is live, or all day inside its dates when it shows outside its
    hours (Offer::isShownAt()). The carousel hides itself when none is
    shown (menu-offers.js keeps it so as the time passes).
--}}
@use('App\Enums\OfferPlacement')
@php $banners = $offers->filter(fn ($offer) => $offer->showsIn(OfferPlacement::Banner)); @endphp
@if ($banners->isNotEmpty())
    <div class="offers-banner" data-offers-row="banner" role="region" aria-roledescription="carousel" aria-label="{{ __('Offers') }}"
         @if ($banners->every(fn ($offer) => ! in_array($offer->id, $offers_shown, true))) hidden @endif>
        <div class="offers-track" data-offers-track>
            @foreach ($banners as $offer)
                @include('menu.partials.offer-card', ['variant' => 'banner', 'live' => in_array($offer->id, $offers_shown, true), 'eager' => $loop->first])
            @endforeach
        </div>
        @if ($banners->count() > 1)
            {{-- Its height kept before the dots arrive, so nothing moves. --}}
            <div class="offers-dots" data-offers-dots></div>
        @endif
    </div>
@endif
