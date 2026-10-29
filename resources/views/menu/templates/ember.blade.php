{{--
    Template: ember (premium)

    Dark and warm, lit like a room at night: the cover fills the top of the
    page behind the logo and the name, the categories are tiles with gold
    line icons, the dishes are dark cards with their photo fading in from the
    end side, and a floating bar at the foot reaches WhatsApp, the
    categories, the language, the map and the cart.

    It receives what every design does (see classic): $restaurant, $template,
    $settings, $locale, $hours, $can_order, $is_preview, $dish_options,
    $menu_token and the rest. Its static styles are public/css/menu-ember.css;
    the owner's fonts (headings too, in a heavier weight) and the design's
    colour variables come from menu.partials.theme. A
    design is its layout only: what the menu does is menu.partials.scripts,
    included last, and this page keeps every hook it looks for.
--}}
@use('App\Models\RestaurantSocialLink')
@use('App\Services\Menu\MenuFonts')
@use('App\Services\Menu\MenuLanguages')
@use('App\Support\Asset')
@use('App\Support\Color')
@use('App\Support\CategoryIcons')
@use('App\Support\MenuIcons')
@use('App\Support\Price')
@php
    $isRtl = MenuLanguages::isRtl($locale);
    // Every piece of text is this language, else the menu's main one, so
    // nothing on the menu is ever blank.
    $text = MenuLanguages::reader($restaurant, $locale);
    $logo = $restaurant->getFirstMediaUrl('logo') ?: null;
    // The cover is the first picture on the page: a phone gets its 960px
    // version, a wide screen the full one.
    $coverMedia = $restaurant->getFirstMedia('cover_image');
    $cover = $coverMedia?->getUrl();
    $coverPhone = $coverMedia?->hasGeneratedConversion('phone') ? $coverMedia->getUrl('phone') : null;
    $mediaHost = parse_url((string) config('filesystems.disks.'.config('media-library.disk_name').'.url'), PHP_URL_HOST);
    $mediaOrigin = $mediaHost && $mediaHost !== request()->getHost() ? 'https://'.$mediaHost : null;
    $currency = config("currencies.{$restaurant->currency}.symbol", $restaurant->currency);
    $name = $text($restaurant, 'name');
    $description = $text($restaurant, 'description');

    // The design's own defaults (the templates row's settings_schema) stand
    // in for a row an admin removed.
    $accent = $settings['primary_color'] ?? '#E4CA55';
    $background = $settings['background_color'] ?? '#0F0F0F';
    $textColor = $settings['text_color'] ?? '#F3EBDD';
    $accentInk = Color::inkOn($accent);

    $icons = MenuIcons::all();
    $canSwitchLocale = count($locales) > 1;
    $table ??= null;

    // Small spaced capitals (the description, a category's line) only where
    // the letters have capitals and keep their shape apart: spacing Arabic
    // or Devanagari letters would break them from each other.
    $spaced = in_array(MenuFonts::scriptOf($locale), ['latin', 'cyrillic'], true);
    // The category tiles' names are a refined serif in Latin letters (one
    // weight, in the fonts' own request); other scripts keep the owner's font.
    $tileFont = MenuFonts::scriptOf($locale) === 'latin' ? ['family' => 'EB Garamond', 'weight' => 400] : null;

    // Asked once: the cover and the cart below both need it. Null without hours.
    $open = null;
    $shownHours = '';

    if (! $hours->isEmpty()) {
        $open = $hours->isOpenNow();
        // Every shift today, "12:00 - 15:00, 18:00 - 23:00"; just after
        // midnight, last night's while it is still open.
        $shownHours = collect($hours->currentShifts())->map(fn (array $shift): string => $shift['open'].' - '.$shift['close'])->implode(', ');
    }

    $platforms = RestaurantSocialLink::LABELS;
    $hasContact = $whatsapp_url || $restaurant->socialLinks->isNotEmpty();

    $visible = $restaurant->categories->filter(fn ($category) => $category->dishes->isNotEmpty());
    // An offer's deal dish not live as the page is drawn is hidden
    // (data-deal-off), and so is a category with nothing else, heading and
    // tile; menu-offers.js shows them when the offer is live.
    $hidden_deals ??= [];
    $dealsOnly = fn ($category): bool => $category->dishes->every(fn ($dish): bool => in_array($dish->id, $hidden_deals, true));
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        // An iPhone zooms in on a focused box whose text is under 16px and
        // stays zoomed; only iPhones and iPads get maximum-scale (Android
        // would lose pinch zoom).
        if (/iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) {
            document.querySelector('meta[name=viewport]').setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1');
        }
    </script>
    {{-- What the cart and the tracking send in place of a CSRF token (App\Services\Menu\MenuToken). --}}
    <meta name="menu-token" content="{{ $menu_token }}">
    <meta name="theme-color" content="{{ $background }}">
    @include('menu.partials.seo')
    @if ($mediaOrigin)
        <link rel="preconnect" href="{{ $mediaOrigin }}">
    @endif

    @include('menu.partials.theme', ['tile_font' => $tileFont])

    <style>
        :root {
            --accent: {{ $accent }};
            --page: {{ $background }};
            --text: {{ $textColor }};
            --accent-ink: {{ $accentInk }};
            /* The page is the owner's background, the darkest thing on it;
               cards, sheets and the cart sit a shade lighter on --bg, which
               is what the shared parts (the cart, the sheets) are drawn on. */
            --bg: color-mix(in srgb, var(--text) 6%, var(--page));
            --soft: color-mix(in srgb, var(--text) 10%, var(--page));
            --line: color-mix(in srgb, var(--text) 13%, var(--page));
            --muted: color-mix(in srgb, var(--text) 64%, var(--page));
            --gold-line: color-mix(in srgb, var(--accent) 55%, var(--page));
            --gold-soft: color-mix(in srgb, var(--accent) 16%, var(--bg));
            color-scheme: {{ Color::isDark($background) ? 'dark' : 'light' }};
        }
    </style>
    <link rel="stylesheet" href="{{ Asset::versioned('css/menu-ember.css') }}">
</head>
<body @class(['has-dock', 'is-spaced' => $spaced])>
<div class="wrap {{ $can_order ? 'with-cart' : '' }}" id="top">
    <main class="col-main">
        <header class="hero">
            {{-- Above the fold, so never lazy: it is what the page waits on. --}}
            @if ($cover)
                <img class="hero-photo" src="{{ $cover }}" alt="" fetchpriority="high" decoding="async"
                     @if ($coverPhone) srcset="{{ $coverPhone }} 960w, {{ $cover }} {{ (int) $coverMedia->getCustomProperty('width', 1920) }}w" sizes="(min-width: 1024px) 1100px, 100vw" @endif>
            @else
                {{-- No cover yet: the default banner, darkened to the room. --}}
                <img class="hero-photo hero-photo-default" src="{{ Asset::versioned('images/menu/default-banner.webp') }}" alt="" fetchpriority="high" decoding="async">
            @endif
            <span class="hero-shade"></span>

            <div class="hero-top">
                {{-- The owner can hide the name beside the logo; the link still
                     carries it for screen readers. --}}
                <a class="brand" href="#top" aria-label="{{ $name }}">
                    @if ($logo)
                        <img class="brand-mark" src="{{ $logo }}" alt="">
                    @else
                        <span class="brand-mark">{{ Str::upper(Str::substr($name, 0, 1)) }}</span>
                    @endif
                    @if ($settings['show_name'] ?? true)
                        <span class="brand-name"><bdi>{{ $name }}</bdi></span>
                    @endif
                </a>

                <div class="hero-pill">
                    @if ($visible->isNotEmpty())
                        {{-- The magnifier opens the box (a tap on the label focuses
                             it), and the bar becomes the search, the dishes it
                             finds straight under it; the close button empties
                             it and brings the menu back (menu-nav.js). --}}
                        <label class="pill-search">
                            <span class="icon icon-search">{!! $icons['search'] !!}</span>
                            <input type="search" data-menu-search autocomplete="off" placeholder="{{ __('Search the menu') }}" aria-label="{{ __('Search the menu') }}">
                        </label>
                        <button type="button" class="pill-close" data-search-close aria-label="{{ __('Close') }}">
                            <span class="icon">{!! $icons['close'] !!}</span>
                        </button>
                    @endif
                    {{-- A phone switches language from the bar at its foot; a
                         wide screen, which has no bar, from here. --}}
                    @if ($canSwitchLocale)
                        <button type="button" class="pill-button on-wide" data-pop-open="lang" aria-haspopup="dialog" aria-label="{{ __('Language') }}">
                            <span class="icon">{!! $icons['language'] !!}</span>
                        </button>
                    @endif
                </div>
            </div>

            {{-- Owner text in <bdi>: a line not yet written in this language
                 shows in the main one, its full stop at its own end. --}}
            <div class="hero-body">
                <h1><bdi>{{ $name }}</bdi></h1>
                @if ($description)
                    <p class="hero-text"><bdi>{{ $description }}</bdi></p>
                @endif

                @if ($open !== null || $table)
                    <div class="hero-facts">
                        @if ($open !== null)
                            <span class="hours">
                                <span class="hours-label {{ $open ? 'fact-open' : 'fact-shut' }}" data-hours-label>{{ $open ? __('Open now') : __('Closed now') }}</span>
                            </span>
                            {{-- The times read left to right whatever the page. --}}
                            <bdi class="hours-value" dir="ltr" data-hours-value>{{ $shownHours !== '' ? $shownHours : __('Closed today') }}</bdi>
                        @endif
                        @if ($table)
                            {{-- Opened from a table's QR code: the table the order goes to. --}}
                            <span class="hero-table">
                                <span class="icon">{!! $icons['table'] !!}</span>
                                <span>{{ __('Your table') }}: <bdi>{{ $table->name }}</bdi></span>
                            </span>
                        @endif
                    </div>
                @endif

                {{-- Reaching the restaurant and sharing the menu; the map is in
                     the bar at the foot on a phone, here on a wide screen. --}}
                <div class="hero-actions">
                    @if ($restaurant->phone)
                        <a class="round-button" href="tel:{{ $restaurant->phone }}" data-track="call" aria-label="{{ __('Call the restaurant') }}">
                            <span class="icon">{!! $icons['phone'] !!}</span>
                        </a>
                    @endif
                    @if ($hasContact)
                        <button type="button" class="round-button" data-pop-open="contact" aria-haspopup="dialog"
                                aria-label="{{ $whatsapp_url ? __('WhatsApp') : __('Follow us') }}">
                            <span class="icon">{!! $whatsapp_url ? $icons['whatsapp'] : $icons['heart'] !!}</span>
                        </button>
                    @endif
                    @if ($restaurant->google_maps_url)
                        <button type="button" class="round-button on-wide" data-pop-open="map" aria-haspopup="dialog" aria-label="{{ __('Find us') }}">
                            <span class="icon">{!! $icons['pin'] !!}</span>
                        </button>
                    @endif
                    {{-- A guest at a laptop scans this to carry the menu to their phone. --}}
                    <button type="button" class="round-button" data-pop-open="qr" aria-haspopup="dialog" aria-label="{{ __('Share menu') }}">
                        <span class="icon">{!! $icons['qr'] !!}</span>
                    </button>
                </div>
            </div>
        </header>

        @include('menu.partials.offers-banner')

        @include('menu.partials.builds')

        @if ($visible->isNotEmpty())
            <nav class="tabs" id="menu-categories" aria-label="{{ __('Categories') }}">
                <div class="tabs-inner">
                    <button type="button" class="tab" data-tab="all" aria-current="true">
                        <span class="tab-icon">{!! $icons['menu'] !!}</span>
                        <span class="tab-name">{{ __('All') }}</span>
                    </button>
                    @foreach ($visible as $category)
                        <button type="button" class="tab" data-tab="category-{{ $category->id }}" aria-current="false" @if ($dealsOnly($category)) hidden data-deal-off @endif>
                            {{-- A category without an icon shows its name alone, centred in the tile. --}}
                            @if ($icon = CategoryIcons::svg($category->icon))
                                <span class="tab-icon">{!! $icon !!}</span>
                            @endif
                            <span class="tab-name"><bdi>{{ $text($category, 'name') }}</bdi></span>
                        </button>
                    @endforeach
                </div>
            </nav>
        @endif

        <div class="sections" id="menu-sections">
            @forelse ($visible as $category)
                <section class="category" id="category-{{ $category->id }}" @if ($dealsOnly($category)) hidden data-deal-off @endif>
                    @php $categoryDescription = $text($category, 'description'); @endphp
                    <div class="category-head">
                        <h2><bdi>{{ $text($category, 'name') }}</bdi></h2>
                        @if ($categoryDescription)
                            <p><bdi>{{ $categoryDescription }}</bdi></p>
                        @endif
                    </div>
                    <div class="dishes">
                        @foreach ($category->dishes as $dish)
                            @php
                                // The card and the cart draw the small version; the
                                // full photo is only fetched for the sheet or the pop-up.
                                $photoMedia = $dish->getFirstMedia('image');
                                $photo = $photoMedia?->getUrl();
                                // A dish without a photo shows the plate picture, never
                                // as a big photo of its own.
                                $image = $photoMedia?->getAvailableUrl(['thumb']) ?? Asset::versioned('images/menu/default-dish-image.webp');
                                $ingredients = $text($dish, 'ingredients');
                                $dishName = $text($dish, 'name');
                                $choices = $dish_options[$dish->id] ?? null;
                                // A dish with no price of its own may be priced by its variants: it starts at 0.
                                $basePrice = $dish->price !== null ? (string) $dish->price : ($choices ? '0.00' : null);
                                $shownPrice = $choices['lowest'] ?? $dish->price;
                                // A discount comes off its price, cheapest variants and
                                // cheapest builds; the rest of the "from" price keeps its price.
                                $reference = $basePrice === null ? null : ($offer_references[$dish->id] ?? $basePrice);
                                $baseExtra = $reference === null ? '0.00' : bcsub($reference, $basePrice, 2);
                                $extra = $reference === null ? '0.00' : bcsub((string) $shownPrice, $reference, 2);
                                // On a live offer: its price, the full one struck through;
                                // on a pair deal a badge only.
                                $deal = $basePrice !== null ? ($offer_deals[$dish->id] ?? null) : null;
                                $pair = $basePrice !== null && $deal === null ? ($offer_pairs[$dish->id] ?? null) : null;
                                // Priced in the second currency: its own symbol, and the rate.
                                $dishRate = $restaurant->rateOf($dish);
                                $dishCurrency = $dishRate === null ? $currency : config('currencies.'.$dish->currency.'.symbol', $dish->currency);
                                $offerPrice = $deal?->discounted((string) $reference, $dishRate);
                            @endphp
                            {{-- A dish with choices opens its sheet (menu-dish.js) when tapped. --}}
                            <article @class(['dish', 'has-choices' => $choices]) data-dish="{{ $dish->id }}" data-name="{{ $dishName }}"
                                     data-image="{{ $image }}" data-photo="{{ $photo }}"
                                     data-price="{{ $basePrice ?? '' }}"
                                     @if ($basePrice !== null) data-full-price="{{ $basePrice }}" data-base-extra="{{ $baseExtra }}" data-extra="{{ $extra }}" @endif
                                     @if ($deal) data-deal-type="{{ $deal->discount_type->value }}" data-deal-value="{{ (float) $deal->discount_value }}" data-deal-by="{{ $deal->id }}" @endif
                                     @if ($pair) data-pair-offer="{{ $pair->id }}" data-pair-value="{{ (float) $pair->discount_value }}" @endif
                                     @if ($choices) data-choices data-ingredients="{{ $ingredients }}" @endif
                                     @if ($dishRate !== null) data-currency="{{ $dishCurrency }}" data-rate="{{ Price::rate($dishRate) }}" @endif
                                     @if ($dish->isDeal()) data-deal-offer="{{ $dish->offer_id }}" @endif
                                     @if (in_array($dish->id, $hidden_deals, true)) hidden data-deal-off @endif
                                     data-search="{{ Str::lower($dishName.' '.$ingredients) }}">
                                <div class="dish-body">
                                    <span class="dish-name"><bdi>{{ $dishName }}</bdi></span>
                                    @if ($ingredients)
                                        <p class="ingredients"><bdi>{{ $ingredients }}</bdi></p>
                                    @endif
                                    <div class="dish-foot">
                                        @if ($basePrice !== null)
                                            <span class="price-group">
                                                <span class="price">{{ Price::money($dishCurrency, $deal ? bcadd($offerPrice, $extra, 2) : $shownPrice) }}</span>
                                                @if ($deal)
                                                    <s class="price-was">{{ Price::money($dishCurrency, $shownPrice) }}</s>
                                                @endif
                                            </span>
                                        @else
                                            <span></span>
                                        @endif
                                        @if ($can_order && $basePrice !== null)
                                            <span class="dish-action"></span>
                                        @elseif ($choices)
                                            {{-- The + button's place and size, so a menu that
                                                 takes no orders looks the same. --}}
                                            <button type="button" class="dish-more" data-dish-open aria-haspopup="dialog"
                                                    aria-label="{{ __('See options') }}">
                                                <span class="icon">{!! $icons['chevron'] !!}</span>
                                            </button>
                                        @endif
                                    </div>
                                </div>
                                {{-- The photo on the end side, fading into the card. --}}
                                @if (! $photoMedia)
                                    <span class="dish-photo-slot">
                                        <img class="dish-photo dish-photo-default" src="{{ $image }}" alt="" width="160" height="128" loading="lazy" decoding="async">
                                    </span>
                                @elseif ($choices)
                                    <span class="dish-photo-slot">
                                        <img class="dish-photo" src="{{ $image }}" alt="{{ $dishName }}" width="160" height="128" loading="lazy" decoding="async">
                                    </span>
                                @else
                                    {{-- Opens the full photo (#pop-photo, menu-nav.js); a dish with
                                         choices shows it at the top of its sheet instead. --}}
                                    <button type="button" class="dish-photo-slot dish-photo-open" data-photo-open aria-haspopup="dialog" aria-label="{{ __('View photo') }}">
                                        <img class="dish-photo" src="{{ $image }}" alt="{{ $dishName }}" width="160" height="128" loading="lazy" decoding="async">
                                    </button>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="empty">{{ __('This menu is still being prepared.') }}</p>
            @endforelse

            <p class="no-results" id="menu-no-results">{{ __('Nothing on the menu matches that.') }}</p>

            <p class="menu-credit">{!! __('Menu by :brand', ['brand' => '<a href="'.e(\App\Support\PortalUrl::to('home', $locale === 'ar' ? 'ar' : 'en')).'">Qayema</a>']) !!}</p>
        </div>
    </main>

    @if ($can_order)
        <aside class="col-aside">
            <h2>{{ __('Your cart') }}</h2>
            <div data-cart-panel="desktop"></div>
        </aside>
    @endif
</div>

{{-- The bar at the foot of a phone: WhatsApp (or the social links, as the
     cover's round button), the categories, the language, the map and the
     cart, each only when it has something to do. The cart's count lives
     here (the side column is the cart on a wide screen). --}}
<nav class="dock" aria-label="{{ __('Menu actions') }}">
    <div class="dock-inner">
        @if ($hasContact)
            <div class="dockitem">
                <button type="button" class="dockface" data-pop-open="contact" aria-haspopup="dialog" aria-label="{{ $whatsapp_url ? __('WhatsApp') : __('Follow us') }}">
                    <span class="icon">{!! $whatsapp_url ? $icons['whatsapp'] : $icons['heart'] !!}</span>
                </button>
            </div>
        @endif
        <a class="dockface is-current" href="{{ $visible->isNotEmpty() ? '#menu-categories' : '#menu-sections' }}" aria-label="{{ __('Menu') }}">
            <span class="icon">{!! $icons['menu'] !!}</span>
        </a>
        @if ($canSwitchLocale)
            <div class="dockitem">
                <button type="button" class="dockface" data-pop-open="lang" aria-haspopup="dialog" aria-label="{{ __('Language') }}">
                    <span class="icon">{!! $icons['language'] !!}</span>
                </button>
            </div>
        @endif
        @if ($restaurant->google_maps_url)
            <div class="dockitem">
                <button type="button" class="dockface" data-pop-open="map" aria-haspopup="dialog" aria-label="{{ __('Location') }}">
                    <span class="icon">{!! $icons['pin'] !!}</span>
                </button>
            </div>
        @endif
        @if ($can_order)
            <button type="button" class="dockface dock-cart" data-open-cart data-cart-toggle aria-label="{{ __('Cart') }}">
                <span class="icon">{!! $icons['cart'] !!}<span class="dock-count" data-cart-count>0</span></span>
            </button>
        @endif
    </div>
</nav>

<dialog class="pop" id="pop-qr">
    <div class="pop-body">
        <h2>{{ __('Scan to open this menu') }}</h2>
        {{-- The owner's saved design, fetched once the page has loaded. --}}
        <div class="qr-code" data-qr-canvas role="img" aria-label="{{ __('QR code for the menu') }}"
             data-options="{{ route('public.qr.options', $restaurant->slug) }}"
             data-lib="{{ Asset::versioned('js/qr-code-styling.js') }}"></div>
        <p class="pop-note" dir="ltr">{{ $menu_url }}</p>
        <button type="button" class="pop-done" data-pop-close>{{ __('Close') }}</button>
    </div>
</dialog>

@if ($canSwitchLocale)
    <dialog class="pop" id="pop-lang">
        <div class="pop-body">
            <h2>{{ __('Language') }}</h2>
            <div class="pop-list">
                @foreach ($locales as $code => $link)
                    <a class="pop-item" href="{{ $link['url'] }}" lang="{{ $code }}" data-track="language" data-track-value="{{ $code }}"
                       @if ($code === $locale) aria-current="true" @endif>
                        <span aria-hidden="true">{{ $link['flag'] }}</span>
                        <span>{{ $link['name'] }}</span>
                    </a>
                @endforeach
            </div>
            <button type="button" class="pop-done" data-pop-close>{{ __('Close') }}</button>
        </div>
    </dialog>
@endif

{{-- A dish's full photo, filled by menu-nav.js; a tap anywhere closes it. --}}
<dialog class="pop photo-pop" id="pop-photo">
    <button type="button" class="dish-sheet-close" data-pop-close aria-label="{{ __('Close') }}">
        <span class="icon">{!! $icons['close'] !!}</span>
    </button>
    <img class="photo-pop-img" data-photo-view data-pop-close alt="">
</dialog>

@if ($restaurant->google_maps_url)
    <dialog class="pop" id="pop-map">
        <div class="pop-body">
            @if ($map_embed_url)
                {{-- OpenStreetMap, keyless; nothing is fetched until it opens.
                     No referrerpolicy: the tiles need a real Referer, which
                     allow-same-origin lets the frame send (see classic). --}}
                <iframe class="pop-map" src="{{ $map_embed_url }}" title="{{ __('Find us') }}"
                        loading="lazy" sandbox="allow-scripts allow-same-origin"></iframe>
            @else
                <span class="pop-icon">{!! $icons['pin'] !!}</span>
            @endif
            <h2>{{ __('Find us') }}</h2>
            <p class="pop-note">{{ __('We will open our location in your maps app.') }}</p>
            {{-- Focused when it opens, so Escape still reaches the dialog. --}}
            <a class="pop-go" href="{{ $restaurant->google_maps_url }}" target="_blank" rel="noopener" data-pop-close data-track="map" autofocus>
                {{ __('Open in Maps') }}
            </a>
            <button type="button" class="pop-done" data-pop-close>{{ __('Close') }}</button>
        </div>
    </dialog>
@endif

@if ($hasContact)
    <dialog class="pop" id="pop-contact">
        <div class="pop-body">
            @if ($whatsapp_url)
                <span class="pop-icon is-whatsapp-ink">{!! $icons['whatsapp'] !!}</span>
                <h2>{{ __('WhatsApp') }}</h2>
                <p class="pop-note">{{ __('We will open a chat with us in WhatsApp.') }}</p>
                <a class="pop-go is-whatsapp-go" href="{{ $whatsapp_url }}" target="_blank" rel="noopener" data-pop-close data-track="whatsapp">
                    {{ __('Open WhatsApp') }}
                </a>
            @endif

            @if ($restaurant->socialLinks->isNotEmpty())
                @if ($whatsapp_url)
                    <p class="pop-subhead">{{ __('Follow us') }}</p>
                @else
                    <h2>{{ __('Follow us') }}</h2>
                @endif
                <div class="pop-list">
                    @foreach ($restaurant->socialLinks as $link)
                        <a class="pop-item is-{{ $link->platform }}" href="{{ $link->url }}" target="_blank" rel="noopener" data-pop-close
                           data-track="social" data-track-value="{{ $link->platform }}">
                            <span class="icon">{!! $icons[$link->platform] ?? $icons['heart'] !!}</span>
                            <span>{{ $platforms[$link->platform] ?? ucfirst($link->platform) }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            <button type="button" class="pop-done" data-pop-close>{{ __('Close') }}</button>
        </div>
    </dialog>
@endif

@include('menu.partials.scripts')
</body>
</html>
