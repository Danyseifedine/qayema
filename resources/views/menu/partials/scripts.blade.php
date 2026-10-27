{{--
    What the menu does, as opposed to how it looks: every design includes
    this once, last in its <body>, and its own markup keeps the hooks these
    scripts look for (data-open-cart, data-cart-panel, data-dish,
    data-pop-open, data-track and the rest; see classic).

    Open or closed is worked out again on the guest's phone
    (menu-hours.js), so a copy of the page that Cloudflare kept is never
    wrong about the time; before the cart, which asks it. Then the cart and
    the tracking (menu.partials.cart), a dish's choices
    (menu.partials.dish-sheet), the visit and what guests do (menu-track.js,
    not in a preview) and the page's own moves (menu-nav.js).

    Expects what PublicMenuController hands every design; works out the
    icons, the currency symbol, the table and open or closed itself when the
    design has not.
--}}
@use('App\Support\Asset')
@use('App\Support\MenuIcons')
@php
    $icons ??= MenuIcons::all();
    $currency ??= config("currencies.{$restaurant->currency}.symbol", $restaurant->currency);
    $table ??= null;
    $offers ??= collect();
    $text ??= \App\Services\Menu\MenuLanguages::reader($restaurant, $locale);
    $open ??= $hours->isEmpty() ? null : $hours->isOpenNow();
@endphp
@unless ($hours->isEmpty())
    <script>
        window.QAYEMA_HOURS = {
            timezone: @js($hours->timezone()),
            week: @js($hours->toArray()),
            labels: { open: @js(__('Open now')), closed: @js(__('Closed now')), closedToday: @js(__('Closed today')) },
        };
    </script>
    <script src="{{ Asset::versioned('js/menu-hours.js') }}" defer></script>
@endunless

{{-- Before the cart, which draws prices from what the offers set. --}}
@if ($offers->isNotEmpty())
    @include('menu.partials.offers-overlay')
@endif

{{-- "Build your own": the price rule the cart and the dish sheet share. --}}
@if (collect($dish_options)->contains(fn (array $choices): bool => $choices['builds'] !== []))
    <script src="{{ Asset::versioned('js/menu-build.js') }}" defer></script>
@endif

@if ($can_order)
    @include('menu.partials.cart')
@endif

@if ($dish_options)
    @include('menu.partials.dish-sheet')
@endif

@unless ($is_preview)
    <script>
        window.QAYEMA_TRACK = {
            url: @js(route('public.events', $restaurant->slug)),
            menu: @js($menu_url),
            locale: @js($locale),
        };
    </script>
    <script src="{{ Asset::versioned('js/menu-track.js') }}" defer></script>
@endunless
<script src="{{ Asset::versioned('js/menu-nav.js') }}" defer></script>
