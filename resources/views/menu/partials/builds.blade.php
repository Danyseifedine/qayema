{{--
    The dishes guests build (Build your own), above the search, under the
    heading "Make it your way": a card each in the menu's accent, its icon
    large and faint in a corner, its name and a "Build yours" button, never a price (it is built first). A card opens its sheet like
    any dish with choices (menu-dish.js), so the cart and the order treat it
    as one. One alone is a wide banner; more are a row swiped sideways.
    Only a dish whose builds can be filled is drawn.
--}}
@use('App\Support\CategoryIcons')
@use('App\Support\Price')
@php $builtShown = ($built_dishes ?? collect())->filter(fn ($dish) => isset($dish_options[$dish->id])); @endphp
@if ($builtShown->isNotEmpty())
    <section class="builds" aria-labelledby="builds-title">
        <h2 class="builds-title" id="builds-title">{{ __('Make it your way') }}</h2>
        <div class="builds-row">
            @foreach ($builtShown as $dish)
                @php
                    $dishName = $text($dish, 'name');
                    $dishRate = $restaurant->rateOf($dish);
                    $dishCurrency = $dishRate === null ? $currency : config('currencies.'.$dish->currency.'.symbol', $dish->currency);
                    $buildIcon = CategoryIcons::svg($dish->icon) ?? CategoryIcons::svg('chef-hat');
                    // An offer's discount comes off what is built too: its "from"
                    // price is the part it comes off (base-extra), as on a dish card.
                    $basePrice = $dish->price === null ? '0.00' : (string) $dish->price;
                    $baseExtra = bcsub((string) ($offer_references[$dish->id] ?? $basePrice), $basePrice, 2);
                    $deal = $offer_deals[$dish->id] ?? null;
                    $pair = $deal === null ? ($offer_pairs[$dish->id] ?? null) : null;
                @endphp
                <article class="dish has-choices dish-build" data-dish="{{ $dish->id }}" data-name="{{ $dishName }}"
                         data-price="{{ $basePrice }}" data-full-price="{{ $basePrice }}" data-base-extra="{{ $baseExtra }}" data-extra="0.00"
                         @if ($deal) data-deal-type="{{ $deal->discount_type->value }}" data-deal-value="{{ (float) $deal->discount_value }}" data-deal-by="{{ $deal->id }}" @endif
                         @if ($pair) data-pair-offer="{{ $pair->id }}" data-pair-value="{{ (float) $pair->discount_value }}" @endif
                         data-choices data-ingredients=""
                         @if ($dishRate !== null) data-currency="{{ $dishCurrency }}" data-rate="{{ Price::rate($dishRate) }}" @endif>
                    <span class="dish-build-mark" aria-hidden="true">{!! $buildIcon !!}</span>
                    <span class="dish-name">{{ $dishName }}</span>
                    <button type="button" class="dish-build-go" data-dish-open aria-haspopup="dialog">
                        <span>{{ __('Build yours') }}</span>
                        <span class="icon">{!! $icons['chevron'] !!}</span>
                    </button>
                </article>
            @endforeach
        </div>
    </section>
@endif
