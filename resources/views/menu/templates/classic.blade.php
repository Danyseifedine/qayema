{{--
    Template: classic (free)

    Every menu template is a standalone page: it receives $restaurant, $template,
    $settings (the owner's choices merged over the template's defaults), $locale
    (the language the owner writes in), $hours, $can_order, $is_preview and
    $dish_options (each dish's variants and add-ons, App\Services\Menu\MenuDishOptions).
    Its static styles are public/css/menu-classic.css; the owner's fonts and
    the design's colour variables come from menu.partials.theme, and only the
    colours this design names differently stay inline below. Run
    `php artisan make:menu-template <slug>` to add a design: it copies this
    file and the stylesheet, and the slug must match the template row's.
--}}
@use('App\Models\Template')
@use('App\Services\Menu\MenuLanguages')
@use('App\Support\Color')
@use('App\Support\MenuIcons')
@use('App\Support\Price')
@php
    $isRtl = MenuLanguages::isRtl($locale);
    // Every piece of text is this language, else the menu's main one (the
    // language every name is required in), so nothing on the menu is ever blank.
    $text = MenuLanguages::reader($restaurant, $locale);
    $logo = $restaurant->getFirstMediaUrl('logo') ?: null;
    // The cover is the first picture on the page: a phone gets its 960px
    // version (registerMediaConversions), a wide screen the full one.
    $coverMedia = $restaurant->getFirstMedia('cover_image');
    $cover = $coverMedia?->getUrl();
    $coverPhone = $coverMedia?->hasGeneratedConversion('phone') ? $coverMedia->getUrl('phone') : null;
    // Pictures come from the media disk's own host; connecting to it early
    // saves a round trip before the first one.
    $mediaHost = parse_url((string) config('filesystems.disks.'.config('media-library.disk_name').'.url'), PHP_URL_HOST);
    $mediaOrigin = $mediaHost && $mediaHost !== request()->getHost() ? 'https://'.$mediaHost : null;
    $currency = config("currencies.{$restaurant->currency}.symbol", $restaurant->currency);
    $name = $text($restaurant, 'name');
    $description = $text($restaurant, 'description');
    $todayRange = $hours->todayRange();

    $accent = $settings['primary_color'] ?? Template::DEFAULT_PRIMARY_COLOR;
    $background = $settings['background_color'] ?? '#FFFFFF';
    // What to print *on* the accent: measured, since the owner picks the colour.
    $accentInk = Color::inkOn($accent);

    $icons = MenuIcons::all();

    // The dock carries the things a guest wants that are otherwise stranded at
    // the top of a long menu: share, language, directions, WhatsApp. The cart
    // stays in the header; with no cart, WhatsApp takes its place there.
    // Each part only appears if the restaurant has it.
    // The QR is always worth offering, so the dock always has something.
    $canSwitchLocale = count($locales) > 1;

    // Built once and laid out twice: as a row in the header on a wide screen,
    // and as the card under the cover on a phone. The card only keeps what the
    // dock does not already carry; on a phone that is just the hours.
    $facts = [];
    $table ??= null;

    // Opened from a table's QR code: the guest sees which table the menu
    // thinks they are at, the one their order will go to.
    if ($table) {
        $facts[] = [
            'icon' => 'table',
            'value' => $table->name,
            'href' => null,
            'tone' => null,
            'ltr' => false,
            'track' => null,
        ];
    }

    if (! $hours->isEmpty()) {
        $open = $hours->isOpenNow();
        $facts[] = [
            'icon' => 'clock',
            'label' => $open ? __('Open now') : __('Closed now'),
            'value' => $todayRange ? $todayRange['open'].' - '.$todayRange['close'] : __('Closed today'),
            'href' => null,
            'tone' => $open ? 'open' : 'shut',
            'ltr' => true,
            'track' => null,
        ];
    }

    if ($restaurant->google_maps_url) {
        $facts[] = [
            'icon' => 'pin',
            'value' => __('Find us'),
            'href' => $restaurant->google_maps_url,
            'tone' => null,
            'ltr' => false,
            'track' => 'map',
        ];
    }

    if ($restaurant->phone) {
        $facts[] = [
            'icon' => 'phone',
            'value' => $restaurant->phone,
            'href' => 'tel:'.$restaurant->phone,
            'tone' => null,
            'ltr' => true,
            'track' => 'call',
        ];
    }

    // The info card under the name shows the hours (the header row has the rest).
    $hoursFact = collect($facts)->firstWhere('icon', 'clock');

    $platforms = ['instagram' => 'Instagram', 'x' => 'X', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'];
    $hasContact = $whatsapp_url || $restaurant->socialLinks->isNotEmpty();

    $visible = $restaurant->categories->filter(fn ($category) => $category->dishes->isNotEmpty());
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <script>
        // An iPhone zooms the page in when a box whose text is under 16px
        // takes focus, and stays zoomed. maximum-scale stops that; iOS still
        // lets the guest pinch to zoom, so only iPhones and iPads get it
        // (Android would lose pinch zoom, and never jumps anyway).
        if (/iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1)) {
            document.querySelector('meta[name=viewport]').setAttribute('content', 'width=device-width, initial-scale=1, maximum-scale=1');
        }
    </script>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="{{ $accent }}">
    @include('menu.partials.seo')
    @if ($mediaOrigin)
        <link rel="preconnect" href="{{ $mediaOrigin }}">
    @endif

    @include('menu.partials.theme')

    <style>
        :root {
            --accent: {{ $accent }};
            --bg: {{ $background }};
            --text: {{ $settings['text_color'] ?? '#111418' }};
            --accent-ink: {{ $accentInk }};
            /* The page is not the card. Everything below the header sits on
               --page and the cards sit on --bg, which is what makes a card
               read as a card. */
            --page: color-mix(in srgb, var(--text) 4.5%, var(--bg));
            --soft: color-mix(in srgb, var(--text) 6%, var(--bg));
            --line: color-mix(in srgb, var(--text) 11%, var(--bg));
            --muted: color-mix(in srgb, var(--text) 64%, var(--bg));
            /* The phone top bar's height, which the sticky category tabs sit
               under. .topbar-inner holds it, so a bigger logo or no cart
               button can never make the tabs slide beneath the bar. */
            --bar: 61px;
            --dock: 0px;
            /* The browser's own scrollbars and controls follow the owner's
               background, light or dark, not the guest's system setting. */
            color-scheme: {{ Color::isDark($background) ? 'dark' : 'light' }};
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/menu-classic.css') }}?v={{ filemtime(public_path('css/menu-classic.css')) }}">
</head>
<body class="has-dock">
<header class="topbar">
    <div class="topbar-inner">
        {{-- The owner can hide the name beside the logo (a logo that already
             spells it out); the link still carries it for screen readers. --}}
        <a class="brand" href="#top" aria-label="{{ $name }}">
            @if ($logo)
                <img class="brand-mark" src="{{ $logo }}" alt="">
            @else
                <span class="brand-mark">{{ Str::upper(Str::substr($name, 0, 1)) }}</span>
            @endif
            @if ($settings['show_name'] ?? true)
                <span class="brand-name">{{ $name }}</span>
            @endif
        </a>

        @if ($facts)
            <div class="facts-row">
                @foreach ($facts as $fact)
                    @if ($fact['href'])
                        <a class="fact" href="{{ $fact['href'] }}" @if ($fact['track']) data-track="{{ $fact['track'] }}" @endif @if ($fact['icon'] === 'pin') target="_blank" rel="noopener" @endif>
                    @else
                        <span class="fact">
                    @endif
                        <span class="icon">{!! $icons[$fact['icon']] !!}</span>
                        @if ($fact['tone'])
                            <span class="fact-{{ $fact['tone'] }}">{{ $fact['label'] }}</span>
                            <span aria-hidden="true">·</span>
                        @endif
                        <span @if ($fact['ltr']) dir="ltr" @endif>{{ $fact['value'] }}</span>
                    {!! $fact['href'] ? '</a>' : '</span>' !!}
                @endforeach
            </div>
        @endif

        @if ($visible->isNotEmpty())
            <label class="field topbar-search">
                <span class="icon icon-search">{!! $icons['search'] !!}</span>
                <input type="search" data-menu-search autocomplete="off" placeholder="{{ __('Search the menu') }}" aria-label="{{ __('Search the menu') }}">
            </label>
        @endif

        @if ($can_order)
            <button type="button" class="topbar-cart" data-open-cart data-cart-toggle aria-label="{{ __('Your cart') }}">
                <span class="icon icon-cart">{!! $icons['cart'] !!}</span>
                <span class="badge" data-cart-count>0</span>
            </button>
        @endif

        @if ($hasContact)
            {{-- Desktop has no dock, so WhatsApp and the social links are
                 reached from here, through the same popup. A phone shows it
                 too when there is no cart, so the bar is never left empty,
                 and the dock then leaves it out. --}}
            <button type="button" @class(['top-action dockface', 'on-phone' => ! $can_order]) data-pop-open="contact" aria-haspopup="dialog"
                    aria-label="{{ $whatsapp_url ? __('WhatsApp') : __('Follow us') }}">
                <span class="icon">{!! $whatsapp_url ? $icons['whatsapp'] : $icons['heart'] !!}</span>
            </button>
        @endif

        @if ($canSwitchLocale)
            <button type="button" class="top-action dockface" data-pop-open="lang" aria-haspopup="dialog"
                    aria-label="{{ __('Language') }}">
                <span class="icon">{!! $icons['language'] !!}</span>
            </button>
        @endif

        {{-- A guest at a laptop scans this to carry the menu to their phone. --}}
        <button type="button" class="top-action dockface" data-pop-open="qr" aria-haspopup="dialog"
                aria-label="{{ __('Share menu') }}">
            <span class="icon">{!! $icons['qr'] !!}</span>
        </button>

    </div>
</header>

<div class="wrap {{ $can_order ? 'with-cart' : '' }}" id="top">
    <main class="col-main">
        <div class="cover">
            @if ($cover)
                {{-- Above the fold, so never lazy: it is what the page waits on. --}}
                <img class="cover-photo" src="{{ $cover }}" alt="" fetchpriority="high" decoding="async"
                     @if ($coverPhone) srcset="{{ $coverPhone }} 960w, {{ $cover }} 1920w" sizes="(min-width: 1024px) 1000px, 100vw" @endif>
            @endif
            <span class="cover-shade"></span>
            <div class="cover-body">
                <h1>{{ $name }}</h1>
                @if ($description)
                    <p>{{ $description }}</p>
                @endif
            </div>
        </div>

        @if ($hoursFact || $table)
            <div class="info">
                @if ($table)
                    <div class="info-row">
                        <span class="info-icon"><span class="icon">{!! $icons['table'] !!}</span></span>
                        <span class="info-text">
                            <span class="info-label">{{ __('Your table') }}</span>
                            <span class="info-value">{{ $table->name }}</span>
                        </span>
                    </div>
                @endif
                @if ($hoursFact)
                <div class="info-row">
                    <span class="info-icon"><span class="icon">{!! $icons['clock'] !!}</span></span>
                    <span class="info-text">
                        <span class="info-label fact-{{ $hoursFact['tone'] }}">{{ $hoursFact['label'] }}</span>
                        {{-- The line follows the page (right in Arabic); only the
                             times inside it read left to right. --}}
                        <span class="info-value"><bdi dir="ltr">{{ $hoursFact['value'] }}</bdi></span>
                    </span>
                </div>
                @endif
            </div>
        @endif

        @if ($visible->isNotEmpty())
            <div class="search">
                <label class="field">
                    <span class="icon icon-search">{!! $icons['search'] !!}</span>
                    <input type="search" data-menu-search autocomplete="off" placeholder="{{ __('Search the menu') }}" aria-label="{{ __('Search the menu') }}">
                </label>
            </div>

            <nav class="tabs" aria-label="{{ __('Categories') }}">
                <div class="tabs-inner">
                    <span class="tab-pill" aria-hidden="true"></span>
                    <button type="button" class="tab" data-tab="all" aria-current="true">{{ __('All') }}</button>
                    @foreach ($visible as $category)
                        <button type="button" class="tab" data-tab="category-{{ $category->id }}" aria-current="false">
                            {{ $text($category, 'name') }}
                        </button>
                    @endforeach
                </div>
            </nav>
        @endif

        <div class="sections">
            @forelse ($visible as $category)
                <section class="category" id="category-{{ $category->id }}">
                    @php $categoryDescription = $text($category, 'description'); @endphp
                    <div class="category-head">
                        <h2>{{ $text($category, 'name') }}</h2>
                        @if ($categoryDescription)
                            <p>{{ $categoryDescription }}</p>
                        @endif
                    </div>
                    <div class="dishes">
                        @foreach ($category->dishes as $dish)
                            @php
                                // The card and the cart draw the small version; the
                                // full photo is only fetched when the dish's sheet opens.
                                // Versioned addresses (media-library.version_urls), so a
                                // remade card picture is fetched again.
                                $photoMedia = $dish->getFirstMedia('image');
                                $photo = $photoMedia?->getUrl();
                                $image = $photoMedia?->getAvailableUrl(['thumb']);
                                $ingredients = $text($dish, 'ingredients');
                                $dishName = $text($dish, 'name');
                                $choices = $dish_options[$dish->id] ?? null;
                                // A dish with no price of its own may be priced by its
                                // variants (Small $7, Large $12): it starts at 0.
                                $basePrice = $dish->price !== null ? (string) $dish->price : ($choices ? '0.00' : null);
                            @endphp
                            {{-- A dish with choices opens its sheet (menu-dish.js) when tapped. --}}
                            <article @class(['dish', 'has-choices' => $choices]) data-dish="{{ $dish->id }}" data-name="{{ $dishName }}"
                                     data-image="{{ $image }}" data-photo="{{ $photo }}"
                                     data-price="{{ $basePrice ?? '' }}"
                                     @if ($choices) data-choices data-ingredients="{{ $ingredients }}" @endif
                                     data-search="{{ Str::lower($dishName.' '.$ingredients) }}">
                                @if ($image && $choices)
                                    <img class="dish-photo" src="{{ $image }}" alt="{{ $dishName }}" width="72" height="72" loading="lazy" decoding="async">
                                @elseif ($image)
                                    {{-- Opens the full photo (#pop-photo, menu-nav.js); a dish with
                                         choices shows it at the top of its sheet instead. --}}
                                    <button type="button" class="dish-photo-open" data-photo-open aria-haspopup="dialog" aria-label="{{ __('View photo') }}">
                                        <img class="dish-photo" src="{{ $image }}" alt="{{ $dishName }}" width="72" height="72" loading="lazy" decoding="async">
                                    </button>
                                @endif
                                <div class="dish-body">
                                    <span class="dish-name">{{ $dishName }}</span>
                                    @if ($ingredients)
                                        <p class="ingredients">{{ $ingredients }}</p>
                                    @endif
                                    <div class="dish-foot">
                                        @if ($basePrice !== null)
                                            <span class="price">{{ $currency }}{{ Price::format($choices['lowest'] ?? $dish->price) }}</span>
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
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <p class="empty">{{ __('This menu is still being prepared.') }}</p>
            @endforelse

            <p class="no-results" id="menu-no-results">{{ __('Nothing on the menu matches that.') }}</p>

            {{-- A quiet credit: guests who like the menu find where it came
                 from, in Arabic for an Arabic menu. --}}
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

<nav class="dock" aria-label="{{ __('Menu actions') }}">
    <div class="dock-inner">
        <div class="dockitem is-share">
            <button type="button" class="dockface" data-pop-open="qr" aria-haspopup="dialog">
                <span class="icon">{!! $icons['qr'] !!}</span>
                <span class="dock-label">{{ __('Share menu') }}</span>
            </button>
        </div>

        @if ($canSwitchLocale)
            <div class="dockitem is-lang">
                <button type="button" class="dockface" data-pop-open="lang" aria-haspopup="dialog">
                    <span class="icon">{!! $icons['language'] !!}</span>
                    <span class="dock-label">{{ $locales[$locale]['name'] ?? __('Language') }}</span>
                </button>
            </div>
        @endif

        @if ($restaurant->google_maps_url)
            <div class="dockitem is-map">
                <button type="button" class="dockface" data-pop-open="map" aria-haspopup="dialog">
                    <span class="icon">{!! $icons['pin'] !!}</span>
                    <span class="dock-label">{{ __('Find us') }}</span>
                </button>
            </div>
        @endif

        @if ($hasContact && $can_order)
            {{-- WhatsApp and the social links share one item: they are the
                 same job, reaching the restaurant. Without WhatsApp the
                 item wears the heart and opens the links alone. With no
                 cart it sits in the top bar instead. --}}
            <div class="dockitem {{ $whatsapp_url ? 'is-whatsapp' : 'is-social' }}">
                <button type="button" class="dockface" data-pop-open="contact" aria-haspopup="dialog">
                    <span class="icon">{!! $whatsapp_url ? $icons['whatsapp'] : $icons['heart'] !!}</span>
                    <span class="dock-label">{{ $whatsapp_url ? __('WhatsApp') : __('Follow us') }}</span>
                </button>
            </div>
        @endif

        <div class="dockitem">
            <button type="button" class="dockface" data-scroll-top aria-label="{{ __('Back to top') }}">
                <span class="icon">{!! $icons['top'] !!}</span>
                <span class="dock-label">{{ __('Back to top') }}</span>
            </button>
        </div>
    </div>
</nav>

<dialog class="pop" id="pop-qr">
    <div class="pop-body">
        <h2>{{ __('Scan to open this menu') }}</h2>
        {{-- The owner's saved design, drawn by the library the dashboard
             previews with. Both are fetched the first time this opens
             rather than on every menu render. --}}
        <div class="qr-code" data-qr-canvas role="img" aria-label="{{ __('QR code for the menu') }}"
             data-options="{{ route('public.qr.options', $restaurant->slug) }}"
             data-lib="{{ asset('js/qr-code-styling.js') }}?v={{ filemtime(public_path('js/qr-code-styling.js')) }}"></div>
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
                {{-- OpenStreetMap, which needs no API key. Inside a closed
                     <dialog> nothing is fetched until the popup is opened.

                     No referrerpolicy: OSM's tile policy requires a real
                     Referer and forbids a restrictive one, and without it the
                     tiles come back 403. `allow-same-origin` is what lets the
                     frame send one. It restores openstreetmap.org's origin,
                     not ours, so the frame still cannot reach this page, and
                     forms, popups and top-level navigation stay blocked. --}}
                <iframe class="pop-map" src="{{ $map_embed_url }}" title="{{ __('Find us') }}"
                        loading="lazy" sandbox="allow-scripts allow-same-origin"></iframe>
            @else
                <span class="pop-icon is-map-ink">{!! $icons['pin'] !!}</span>
            @endif
            <h2>{{ __('Find us') }}</h2>
            <p class="pop-note">{{ __('We will open our location in your maps app.') }}</p>
            <a class="pop-go" href="{{ $restaurant->google_maps_url }}" target="_blank" rel="noopener" data-pop-close data-track="map">
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

@if ($can_order)
    <dialog class="sheet" id="cart-sheet">
        <div class="sheet-body">
            <div class="sheet-head">
                <button type="button" class="sheet-close" data-close-cart aria-label="{{ __('Close') }}">
                    <span class="icon icon-back">{!! $icons['back'] !!}</span>
                </button>
                <h2>{{ __('Your cart') }}</h2>
                <span style="width:36px"></span>
            </div>
            <div class="sheet-scroll" data-cart-panel="sheet"></div>
            <div class="sheet-foot" data-cart-foot></div>
        </div>
    </dialog>

    @php
        // Ordering in the menu: who to call and where to bring it. On
        // WhatsApp the guest only adds a note.
        $inMenu = $order_channel === \App\Enums\OrderChannel::Menu;
        // Live order tracking through Pusher, when its keys are set; the
        // public key and cluster only, the secret stays on the server.
        $pusher = config('broadcasting.default') === 'pusher' && config('broadcasting.connections.pusher.key')
            ? [
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'script' => asset('js/pusher.min.js').'?v='.filemtime(public_path('js/pusher.min.js')),
            ]
            : null;
        $guestCountries = $inMenu
            ? collect(config('countries'))->map(fn (array $country, string $code): array => [
                'code' => $code,
                'flag' => $country['flag'],
                'dial' => $country['dial'],
                // In the menu's language, to read and to search; the English
                // name is searched too.
                'name' => class_exists(\Locale::class) ? (\Locale::getDisplayRegion('-'.$code, $locale) ?: $country['label']) : $country['label'],
                'label' => $country['label'],
            ])->values()->all()
            : [];
    @endphp
    <script>
        window.QAYEMA_MENU = {
            orderUrl: @js(route('public.order', $restaurant->slug)),
            locale: @js($locale),
            currency: @js($currency),
            storageKey: @js('qayema-cart-'.$restaurant->slug),
            mode: @js($order_channel->value),
            // The table whose QR code opened the menu, and where the page
            // remembers it, so a reload or a language switch stays at it.
            table: @js($table ? ['code' => $table->code, 'name' => $table->name] : null),
            tableKey: @js('qayema-table-'.$restaurant->slug),
            @if ($inMenu)
            guestKey: @js('qayema-guest-'.$restaurant->slug),
            addressUrl: @js(route('public.address', $restaurant->slug)),
            // Delivery and pickup taken in the menu; dine-in is its own
            // feature, offered only at a table (menu-cart.js types()).
            types: @js($order_types ?? []),
            dineIn: @js($dine_in ?? false),
            country: @js(isset(config('countries')[$restaurant->country_code]) ? $restaurant->country_code : array_key_first(config('countries'))),
            countries: @js($guestCountries),
            // An order placed in the menu waits for someone to read it, so
            // outside the hours the cart only shows what was picked.
            closed: @js(! $hours->isEmpty() && ! $hours->isOpenNow()),
            @endif
            icons: { plus: @js($icons['plus']), minus: @js($icons['minus']), chevron: @js($icons['chevron']), check: @js($icons['check']), close: @js($icons['close']), pin: @js($icons['pin']) },
            strings: {
                empty: @js(__('Nothing added yet.')),
                each: @js(__('each')),
                item: @js(__('item')),
                items: @js(__('items')),
                place: @js(__('Place order')),
                placing: @js(__('Sending…')),
                total: @js(__('Total')),
                failed: @js(__('That did not send. Please try again.')),
                remove: @js(__('Remove')),
                add: @js(__('Add')),
                options: @js(__('See options')),
                close: @js(__('Close')),
                optional: @js(__('Optional')),
                note: @js(__('Note for the restaurant')),
                noteHint: @js(__('Anything they should know?')),
                check: @js(__('Check the details above.')),
                forTable: @js(__('This order is for :table.')),
                @if ($inMenu)
                how: @js(__('How would you like it?')),
                delivery: @js(__('Delivery')),
                pickup: @js(__('Pickup')),
                dine_in: @js(__('At my table')),
                needsTable: @js(__('Scan the QR code on your table to order.')),
                needsTableShort: @js(__('Scan your table to order')),
                name: @js(__('Your name')),
                nameHint: @js(__('First and last name')),
                nameMissing: @js(__('Add your name so the restaurant knows who to ask for.')),
                phone: @js(__('Phone number')),
                phoneHint: @js(__('Mobile number')),
                country: @js(__('Country code')),
                countrySearch: @js(__('Search a country or code')),
                countryNone: @js(__('No country matches that.')),
                address: @js(__('Address')),
                addressHint: @js(__('Street, building, floor')),
                locate: @js(__('Use my current location')),
                locating: @js(__('Finding your location…')),
                located: @js(__('Location added')),
                locatedFilled: @js(__('We filled in your street. Add the building and floor.')),
                locateFailed: @js(__('We could not get your location. Your address is enough.')),
                phoneMissing: @js(__('Add your phone number so the restaurant can call you.')),
                phoneInvalid: @js(__('Check your phone number.')),
                addressMissing: @js(__('Add your address for the delivery.')),
                closed: @js(__('Closed now')),
                closedNote: @js(__('We are closed right now. Ordering opens again when we do.')),
                sent: @js(__('Order sent')),
                track: @js(__('Track your order')),
                editing: @js(__('Changing order #:reference')),
                adding: @js(__('Adding to your order #:reference')),
                addToOrder: @js(__('Add to order')),
                added: @js(__('Added to your order')),
                addedBody: @js(__('The restaurant will see what you added.')),
                waitingAccepted: @js(__('Your order #:reference is being prepared. You can order again once it is done.')),
                waitingDelivery: @js(__('Your order #:reference is on its way. You can order again once it arrives.')),
                waitingPickup: @js(__('Your order #:reference is ready for pickup. You can order again once you have it.')),
                waitingDineIn: @js(__('Your order #:reference is coming to your table. You can order again once it is served.')),
                oneAtATime: @js(__('One order at a time')),
                keepOrder: @js(__('Keep it as it was')),
                update: @js(__('Update order')),
                updated: @js(__('Order updated')),
                updatedBody: @js(__('The restaurant will see your changes.')),
                unavailable: @js(__('Sent. :dishes is no longer available, so it was taken off your order.')),
                locked: @js(__('This order can no longer be changed')),
                lockedBody: @js(__('The restaurant has already accepted it. Call them if you need to change something.')),
                sentBody: @js(__('The restaurant will call you to confirm. Your order number is :reference.')),
                sentBodyDineIn: @js(__('The restaurant has your order for :table. Your order number is :reference.')),
                @endif
            },
        };
    </script>
    <script src="{{ asset('js/menu-cart.js') }}?v={{ filemtime(public_path('js/menu-cart.js')) }}" defer></script>

    @if ($inMenu)
        {{-- Following an order placed in the menu, in a sheet over it
             (public/js/menu-order.js); live through Pusher when the keys
             are set, its library loaded only when there is an order. --}}
        <dialog class="dish-sheet track-sheet" id="track-sheet" aria-labelledby="track-sheet-title">
            <div class="dish-sheet-body">
                <div class="track-sheet-head">
                    <h2 id="track-sheet-title">{{ __('Order tracking') }}</h2>
                    <button type="button" class="track-sheet-close" data-track-close aria-label="{{ __('Close') }}">
                        <span class="icon">{!! $icons['close'] !!}</span>
                    </button>
                </div>
                <div class="dish-sheet-scroll track-sheet-scroll" data-track-body></div>
            </div>
        </dialog>

        <script>
            window.QAYEMA_ORDERS = {
                orderUrl: @js(route('public.order', $restaurant->slug)),
                orderKey: @js('qayema-order-'.$restaurant->slug),
                pusher: @js($pusher),
                icons: { cart: @js($icons['cart']), close: @js($icons['close']) },
                strings: {
                    close: @js(__('Close')),
                    track: @js(__('Track your order')),
                    trackShort: @js(__('Track')),
                    yourOrder: @js(__('Your order #:reference')),
                    statusWaiting: @js(__('Waiting for the restaurant')),
                    statusAccepted: @js(__('Accepted, being prepared')),
                    statusOnItsWay: @js(__('On its way')),
                    statusReady: @js(__('Ready for pickup')),
                    statusDelivered: @js(__('Delivered')),
                    statusPickedUp: @js(__('Picked up')),
                    statusComing: @js(__('Coming to your table')),
                    statusServed: @js(__('Served')),
                    statusCancelled: @js(__('Cancelled')),
                },
            };
        </script>
        <script src="{{ asset('js/menu-order.js') }}?v={{ filemtime(public_path('js/menu-order.js')) }}" defer></script>
    @endif
@endif

@if ($dish_options)
    {{-- One sheet for every dish with choices, filled by menu-dish.js from
         the data below: a bottom sheet on a phone, a card on a wide screen.
         Without ordering it only shows the choices and their prices. --}}
    <dialog class="dish-sheet" id="dish-sheet" aria-labelledby="dish-sheet-title">
        <div class="dish-sheet-body">
            <button type="button" class="dish-sheet-close" data-dish-close aria-label="{{ __('Close') }}">
                <span class="icon">{!! $icons['close'] !!}</span>
            </button>
            <div class="dish-sheet-scroll">
                <img class="dish-sheet-photo" data-dish-photo alt="" hidden>
                <div class="dish-sheet-head">
                    <h2 id="dish-sheet-title" data-dish-title></h2>
                    <span class="dish-sheet-price" data-dish-price></span>
                    <p class="dish-sheet-ingredients" data-dish-ingredients></p>
                </div>
                <div data-dish-groups></div>
            </div>
            <div class="dish-sheet-foot" data-dish-foot></div>
        </div>
    </dialog>

    <script type="application/json" id="dish-options">@json($dish_options)</script>
    <script>
        window.QAYEMA_DISH = {
            currency: @js($currency),
            canOrder: @js($can_order),
            icons: { plus: @js($icons['plus']), minus: @js($icons['minus']), check: @js($icons['check']) },
            strings: {
                pickOne: @js(__('Pick 1')),
                optional: @js(__('Optional')),
                addons: @js(__('Add-ons')),
                add: @js(__('Add')),
                remove: @js(__('Remove')),
                quantity: @js(__('Quantity')),
                free: @js(__('Free')),
            },
        };
    </script>
    <script src="{{ asset('js/menu-dish.js') }}?v={{ filemtime(public_path('js/menu-dish.js')) }}" defer></script>
@endif

@unless ($is_preview)
    <script>
        window.QAYEMA_TRACK = { url: @js(route('public.events', $restaurant->slug)) };
    </script>
    <script src="{{ asset('js/menu-track.js') }}?v={{ filemtime(public_path('js/menu-track.js')) }}" defer></script>
@endunless
<script src="{{ asset('js/menu-nav.js') }}?v={{ filemtime(public_path('js/menu-nav.js')) }}" defer></script>
</body>
</html>
