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
@php
    $isRtl = MenuLanguages::isRtl($locale);
    // Every piece of text is this language, else English (the language every
    // name is required in), so nothing on the menu is ever blank.
    $text = fn ($model, string $field): string => MenuLanguages::text($model, $field, $locale);
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

    if (! $hours->isEmpty()) {
        $open = $hours->isOpenNow();
        $facts[] = [
            'icon' => 'clock',
            'label' => $open ? __('Open now') : __('Closed now'),
            'value' => $todayRange ? $todayRange['open'].' - '.$todayRange['close'] : __('Closed today'),
            'href' => null,
            'tone' => $open ? 'open' : 'shut',
            'ltr' => true,
            'in_card' => true,
            'track' => null,
        ];
    }

    if ($restaurant->google_maps_url) {
        $facts[] = [
            'icon' => 'pin',
            'label' => __('Location'),
            'value' => __('Find us'),
            'href' => $restaurant->google_maps_url,
            'tone' => null,
            'ltr' => false,
            'in_card' => false,
            'track' => 'map',
        ];
    }

    if ($restaurant->phone) {
        $facts[] = [
            'icon' => 'phone',
            'label' => __('Phone'),
            'value' => $restaurant->phone,
            'href' => 'tel:'.$restaurant->phone,
            'tone' => null,
            'ltr' => true,
            'in_card' => false,
            'track' => 'call',
        ];
    }

    $cardFacts = array_filter($facts, fn (array $fact): bool => $fact['in_card']);

    $platforms = ['instagram' => 'Instagram', 'x' => 'X', 'facebook' => 'Facebook', 'tiktok' => 'TikTok'];
    $hasContact = $whatsapp_url || $restaurant->socialLinks->isNotEmpty();

    $visible = $restaurant->categories->filter(fn ($category) => $category->dishes->isNotEmpty());
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
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
            --bg: {{ $settings['background_color'] ?? '#FFFFFF' }};
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

        @if ($cardFacts)
            <div class="info">
                @foreach ($cardFacts as $fact)
                    @if ($fact['href'])
                        <a class="info-row" href="{{ $fact['href'] }}" @if ($fact['track']) data-track="{{ $fact['track'] }}" @endif @if ($fact['icon'] === 'pin') target="_blank" rel="noopener" @endif>
                    @else
                        <div class="info-row">
                    @endif
                        <span class="info-icon"><span class="icon">{!! $icons[$fact['icon']] !!}</span></span>
                        <span class="info-text">
                            <span class="info-label {{ $fact['tone'] ? 'fact-'.$fact['tone'] : '' }}">{{ $fact['label'] }}</span>
                            <span class="info-value" @if ($fact['ltr']) dir="ltr" @endif>{{ $fact['value'] }}</span>
                        </span>
                    {!! $fact['href'] ? '</a>' : '</div>' !!}
                @endforeach
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
                                $photoMedia = $dish->getFirstMedia('image');
                                $photo = $photoMedia?->getUrl();
                                $image = $photoMedia?->getAvailableUrl(['thumb']);
                                $ingredients = $text($dish, 'ingredients');
                                $dishName = $text($dish, 'name');
                                $choices = $dish_options[$dish->id] ?? null;
                            @endphp
                            {{-- A dish with choices opens its sheet (menu-dish.js) when tapped. --}}
                            <article @class(['dish', 'has-choices' => $choices]) data-dish="{{ $dish->id }}" data-name="{{ $dishName }}"
                                     data-image="{{ $image }}" data-photo="{{ $photo }}"
                                     data-price="{{ $dish->price !== null ? (string) $dish->price : '' }}"
                                     @if ($choices) data-choices data-ingredients="{{ $ingredients }}" @endif
                                     data-search="{{ Str::lower($dishName.' '.$ingredients) }}">
                                @if ($image)
                                    <img class="dish-photo" src="{{ $image }}" alt="{{ $dishName }}" width="72" height="72" loading="lazy" decoding="async">
                                @endif
                                <div class="dish-body">
                                    <span class="dish-name">{{ $dishName }}</span>
                                    @if ($ingredients)
                                        <p class="ingredients">{{ $ingredients }}</p>
                                    @endif
                                    <div class="dish-foot">
                                        @if ($dish->price !== null)
                                            <span class="price">{{ $currency }}{{ number_format((float) ($choices['lowest'] ?? $dish->price), 2) }}</span>
                                        @else
                                            <span></span>
                                        @endif
                                        @if ($can_order && $dish->price !== null)
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

    <script>
        window.QAYEMA_MENU = {
            orderUrl: @js(route('public.order', $restaurant->slug)),
            locale: @js($locale),
            currency: @js($currency),
            storageKey: @js('qayema-cart-'.$restaurant->slug),
            icons: { plus: @js($icons['plus']), minus: @js($icons['minus']), chevron: @js($icons['chevron']) },
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
            },
        };
    </script>
    <script src="{{ asset('js/menu-cart.js') }}?v={{ filemtime(public_path('js/menu-cart.js')) }}" defer></script>
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
                close: @js(__('Close')),
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
