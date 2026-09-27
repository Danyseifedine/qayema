{{--
    Template: classic (free)

    Every menu template is a standalone page: it receives $restaurant, $template,
    $settings (the owner's choices merged over the template's defaults), $locale
    (the language the owner writes in), $hours, $can_order and $is_preview.
    Copy this file to add a new one — the slug of the Blade file must match the
    template row's slug.
--}}
@php
    $isRtl = \App\Services\Global\MenuLanguages::isRtl($locale);
    // Every piece of text is this language, else English — the language every
    // name is required in — so nothing on the menu is ever blank.
    $text = fn ($model, string $field): string => \App\Services\Global\MenuLanguages::text($model, $field, $locale);
    $logo = $restaurant->getFirstMediaUrl('logo') ?: null;
    $cover = $restaurant->getFirstMediaUrl('cover_image') ?: null;
    $currency = config("currencies.{$restaurant->currency}.symbol", $restaurant->currency);
    $name = $text($restaurant, 'name');
    $description = $text($restaurant, 'description');
    $todayRange = $hours->todayRange();

    $accent = $settings['primary_color'] ?? '#1F6FEB';

    // What to print *on* the accent. A pale accent needs near-black, a strong
    // one needs white, and the owner picks the colour — so it is measured, not
    // guessed. CSS has no contrast function, hence the luminance here.
    $hex = ltrim($accent, '#');
    $hex = strlen($hex) === 3 ? $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2] : $hex;
    $rgb = strlen($hex) === 6 && ctype_xdigit($hex) ? array_map('hexdec', str_split($hex, 2)) : [31, 111, 235];
    $accentInk = (0.2126 * $rgb[0] + 0.7152 * $rgb[1] + 0.0722 * $rgb[2]) / 255 > 0.62 ? '#111418' : '#FFFFFF';

    // A script Inter does not cover (Arabic, Chinese, Devanagari) gets its own
    // font, loaded only on that language's menu. Quotes cannot go through
    // {{ }} — it escapes them to &#039; and the whole CSS declaration is then
    // invalid.
    $scriptFont = \App\Services\Global\MenuLanguages::font($locale);
    $fontStack = $scriptFont ? "'{$scriptFont}', 'Inter'" : "'Inter'";
    $fontFamilies = 'family=Inter:wght@400;500;600;700'.($scriptFont ? '&family='.str_replace(' ', '+', $scriptFont).':wght@400;500;600;700' : '');

    $icons = [
        'clock' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/><path d="M12 7v5l3 3"/></svg>',
        'pin' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/><path d="M17.657 16.657l-4.243 4.243a2 2 0 0 1 -2.827 0l-4.244 -4.243a8 8 0 1 1 11.314 0"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M5 4h3l2 5-2 1a11 11 0 0 0 6 6l1-2 5 2v3a2 2 0 0 1-2 2A17 17 0 0 1 3 6a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>',
        'search' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"/><path d="M21 21l-6 -6"/></svg>',
        'cart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 19m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M17 17h-11v-14h-2"/><path d="M6 5l14 1l-1 7h-13"/></svg>',
        'back' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l14 0"/><path d="M5 12l6 6"/><path d="M5 12l6 -6"/></svg>',
        'plus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>',
        'minus' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12l14 0"/></svg>',
        'language' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6.371c0 4.418 -2.239 6.629 -5 6.629"/><path d="M4 6.371h7"/><path d="M5 9c0 2.144 2.252 3.908 6 4"/><path d="M12 20l4 -9l4 9"/><path d="M19.1 18h-6.2"/><path d="M6.694 3l.793 .582"/></svg>',
        'qr' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="M3 7v-2a2 2 0 0 1 2 -2h2"/><path d="M3 17v2a2 2 0 0 0 2 2h2"/><path d="M17 3h2a2 2 0 0 1 2 2v2"/><path d="M17 21h2a2 2 0 0 0 2 -2v-2"/></svg>',
        'whatsapp' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21l1.65 -3.8a9 9 0 1 1 3.4 2.9l-5.05 .9"/><path d="M9 10a.5 .5 0 0 0 1 0v-1a.5 .5 0 0 0 -1 0v1a5 5 0 0 0 5 5h1a.5 .5 0 0 0 0 -1h-1a.5 .5 0 0 0 0 1"/></svg>',
        'top' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 5l0 14"/><path d="M18 11l-6 -6"/><path d="M6 11l6 -6"/></svg>',
        'heart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19.5 12.572l-7.5 7.428l-7.5 -7.428a5 5 0 1 1 7.5 -6.566a5 5 0 1 1 7.5 6.572"/></svg>',
        'instagram' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 8a4 4 0 0 1 4 -4h8a4 4 0 0 1 4 4v8a4 4 0 0 1 -4 4h-8a4 4 0 0 1 -4 -4l0 -8"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/><path d="M16.5 7.5v.01"/></svg>',
        'facebook' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 10v4h3v7h4v-7h3l1 -4h-4v-2a1 1 0 0 1 1 -1h3v-4h-3a5 5 0 0 0 -5 5v2h-3"/></svg>',
        'x' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 4l11.733 16h4.267l-11.733 -16l-4.267 0"/><path d="M4 20l6.768 -6.768m2.46 -2.46l6.772 -6.772"/></svg>',
        'tiktok' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 7.917v4.034a9.948 9.948 0 0 1 -5 -1.951v4.5a6.5 6.5 0 1 1 -8 -6.326v4.326a2.5 2.5 0 1 0 4 2v-11.5h4.083a6.005 6.005 0 0 0 4.917 4.917"/></svg>',
    ];

    // The dock carries the things a guest wants that are otherwise stranded at
    // the top of a long menu: share, language, directions, WhatsApp. The cart
    // stays in the header. Each part only appears if the restaurant has it.
    $canSwitchLocale = count($locales) > 1;
    // The QR is always worth offering, so the dock always has something.
    $hasBottomBar = true;

    // Built once and laid out twice: as a row in the header on a wide screen,
    // and as the card under the cover on a phone. The card only keeps what the
    // dock does not already carry — on a phone that is just the hours.
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
    <title>{{ $name }}</title>
    <meta name="description" content="{{ $description ?: $name }}">

    @foreach ($locales as $code => $link)
        <link rel="alternate" hreflang="{{ $code }}" href="{{ $link['url'] }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ route('public.menu', $restaurant->slug) }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?{!! $fontFamilies !!}&display=swap" rel="stylesheet">

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
            --muted: color-mix(in srgb, var(--text) 58%, var(--bg));
            --font: {!! $fontStack !!}, -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
            --bar: 49px;
            --dock: 0px;
        }

        * { box-sizing: border-box; }

        html { scroll-behavior: smooth; }

        body {
            margin: 0;
            background: var(--page);
            color: var(--text);
            font-family: var(--font);
            font-size: 14px;
            line-height: 1.45;
            -webkit-font-smoothing: antialiased;
        }

        button, input, textarea { font: inherit; color: inherit; }

        .icon { width: 15.5px; height: 15.5px; flex-shrink: 0; }
        .icon-search { width: 17px; height: 17px; }
        .icon-cart { width: 20px; height: 20px; }
        .icon-back { width: 22px; height: 22px; }
        [dir="rtl"] .icon-back { transform: scaleX(-1); }

        /* ---- Top bar ---- */
        .topbar {
            position: sticky;
            top: 0;
            z-index: 20;
            background: var(--bg);
            border-bottom: 1px solid var(--line);
        }
        .topbar-inner {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 14px;
        }
        .brand { display: flex; align-items: center; gap: 8px; text-decoration: none; color: inherit; min-width: 0; }
        .brand-mark {
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            border-radius: 50%;
            object-fit: cover;
            background: var(--accent);
            color: var(--accent-ink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: -0.02em;
        }
        .brand-name { font-size: 14px; font-weight: 600; letter-spacing: -0.01em; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

        .facts-row { display: none; align-items: center; flex: 1; min-width: 0; color: var(--muted); font-size: 13px; }
        .fact { display: inline-flex; align-items: center; gap: 6px; color: inherit; text-decoration: none; white-space: nowrap; }
        .fact + .fact::before { content: ""; width: 1px; height: 14px; background: var(--line); margin: 0 14px; }
        a.fact:hover { color: var(--text); }
        .fact-open { color: #17803d; font-weight: 600; }
        .fact-shut { color: var(--muted); font-weight: 600; }

        /* ---- Search field (one style, two placements) ---- */
        .field {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 10px;
            background: var(--bg);
            border: 1px solid var(--line);
            color: var(--muted);
        }
        .field input { flex: 1; min-width: 0; border: 0; background: transparent; font-size: 13px; outline: none; }
        .field:focus-within { border-color: var(--accent); }
        /* The header search is the wide-screen placement; a phone gets the
           one under the cover instead. */
        .field.topbar-search { display: none; }

        /* ---- Shell ---- */
        .col-main { min-width: 0; }

        /* ---- Cover ---- */
        .cover {
            position: relative;
            height: 160px;
            overflow: hidden;
            background: linear-gradient(135deg,
                color-mix(in srgb, var(--accent) 90%, #000) 0%,
                var(--accent) 50%,
                color-mix(in srgb, var(--accent) 72%, #fff) 100%);
        }
        .cover-photo { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }
        .cover-shade {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(circle at 80% 20%, rgba(255,255,255,.18) 0%, transparent 50%),
                radial-gradient(circle at 10% 90%, rgba(0,0,0,.15) 0%, transparent 45%);
        }
        /* The owner's photo shows as itself. Only enough black is laid over it
           to keep the name readable: a wash rising from the bottom, and one
           coming in behind the text from the side it starts on. */
        .cover-photo + .cover-shade {
            background:
                linear-gradient(to top, rgba(0,0,0,.85) 0%, rgba(0,0,0,.58) 40%, rgba(0,0,0,.24) 72%, rgba(0,0,0,.08) 100%),
                linear-gradient(to right, rgba(0,0,0,.76) 0%, rgba(0,0,0,.34) 45%, transparent 82%);
        }
        /* The title hangs off the inline start, which flips on an Arabic menu;
           gradients are physical, so the side wash flips with it. */
        [dir="rtl"] .cover-photo + .cover-shade {
            background:
                linear-gradient(to top, rgba(0,0,0,.85) 0%, rgba(0,0,0,.58) 40%, rgba(0,0,0,.24) 72%, rgba(0,0,0,.08) 100%),
                linear-gradient(to left, rgba(0,0,0,.76) 0%, rgba(0,0,0,.34) 45%, transparent 82%);
        }
        .cover-body { position: absolute; inset: 0; z-index: 2; display: flex; flex-direction: column; justify-content: flex-end; padding: 20px; }
        .cover-body h1 { margin: 0; font-size: 26px; font-weight: 700; letter-spacing: -0.02em; line-height: 1; color: #fff; }
        .cover-body p { margin: 6px 0 0; font-size: 13px; font-weight: 500; letter-spacing: -0.005em; color: rgba(255,255,255,.88); }

        /* ---- Info card ---- */
        .info { margin: 14px 16px 0; background: var(--bg); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
        .info-row { display: flex; align-items: center; gap: 10px; padding: 8px 12px; color: inherit; text-decoration: none; }
        .info-row + .info-row { border-top: 1px solid var(--line); }
        .info-icon { flex-shrink: 0; width: 28px; height: 28px; border-radius: 7px; background: var(--soft); color: var(--muted); display: flex; align-items: center; justify-content: center; }
        .info-text { flex: 1; min-width: 0; }
        .info-label { display: block; font-size: 11px; line-height: 1.3; color: var(--muted); font-weight: 500; }
        .info-label.fact-open { color: #17803d; }
        .info-value { display: block; font-size: 13px; line-height: 1.3; font-weight: 500; margin-top: 1px; }

        .search { padding: 16px 16px 4px; }

        /* ---- Category tabs ---- */
        .tabs { position: sticky; top: var(--bar); z-index: 10; margin-top: 8px; background: var(--page); border-bottom: 1px solid var(--line); padding: 8px 0; }
        .tabs-inner { position: relative; display: flex; gap: 4px; overflow-x: auto; padding: 0 12px; scrollbar-width: none; }
        .tabs-inner::-webkit-scrollbar { display: none; }
        .tab { position: relative; z-index: 1; flex-shrink: 0; padding: 8px 14px; border-radius: 8px; border: 0; background: transparent; color: var(--text); font-size: 13.5px; font-weight: 500; cursor: pointer; white-space: nowrap; transition: color .32s cubic-bezier(.2,.9,.3,1); }
        .tab[aria-current="true"] { color: var(--accent-ink); font-weight: 600; }
        /* One pill for the whole row, moved by menu-nav.js. It is armed only
           after its first placement so it does not slide in from the left. */
        .tab-pill { position: absolute; top: 0; left: 0; width: 0; height: 100%; border-radius: 8px; background: var(--accent); pointer-events: none; }
        .tab-pill.ready { transition: transform .32s cubic-bezier(.2,.9,.3,1), width .32s cubic-bezier(.2,.9,.3,1); }

        /* ---- Dishes ---- */
        /* Room at the end so the last dish scrolls clear of the floating dock. */
        .sections { padding-bottom: calc(24px + var(--dock) + env(safe-area-inset-bottom)); }
        .category { scroll-margin-top: calc(var(--bar) + 44px); }
        .category-head { padding: 20px 16px 10px; }
        .category-head h2 { margin: 0; font-size: 17px; font-weight: 700; letter-spacing: -0.02em; }
        .category-head p { margin: 3px 0 0; font-size: 12.5px; color: var(--muted); line-height: 1.4; }
        .dishes { display: flex; flex-direction: column; gap: 10px; padding: 0 16px; }
        .dish { display: flex; gap: 12px; padding: 12px; background: var(--bg); border: 1px solid var(--line); border-radius: 12px; }
        .dish-photo { flex-shrink: 0; width: 88px; height: 88px; border-radius: 8px; object-fit: cover; background: var(--soft); }
        .dish-body { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .dish-name { font-size: 15px; font-weight: 600; letter-spacing: -0.005em; line-height: 1.3; }
        .ingredients { margin: 2px 0 0; font-size: 11.5px; color: var(--muted); line-height: 1.4; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
        .dish-foot { margin-top: auto; padding-top: 8px; display: flex; align-items: center; justify-content: space-between; gap: 8px; }
        .price { font-size: 14px; font-weight: 600; font-variant-numeric: tabular-nums; }

        /* ---- Add / quantity ---- */
        .add, .qty button { border: 0; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: transform .12s ease; }
        .add:active, .qty button:active { transform: scale(.88); }
        .add { width: 32px; height: 32px; border-radius: 50%; background: var(--accent); color: var(--accent-ink); box-shadow: 0 1px 2px rgba(0,0,0,.08); }
        .add .icon { width: 18px; height: 18px; }
        .qty { display: inline-flex; align-items: center; background: var(--soft); border-radius: 999px; padding: 3px; }
        .qty button { width: 28px; height: 28px; border-radius: 50%; background: transparent; }
        .qty button .icon { width: 18px; height: 18px; }
        .qty button.plus { background: var(--accent); color: var(--accent-ink); }
        /* The minus is a thin glyph inside a transparent button; the plus is a
           solid disc. Without this the count reads as stuck to the plus. */
        .qty output { min-width: 22px; padding-inline-end: 8px; text-align: center; font-size: 12px; font-weight: 600; font-variant-numeric: tabular-nums; }

        .cart-count { min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px; background: color-mix(in srgb, var(--accent-ink) 25%, transparent); display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; }
        [data-cart-total] { font-variant-numeric: tabular-nums; }

        /* ---- Cart panel ---- */
        .cart-items { font-size: 11.5px; font-weight: 500; letter-spacing: .06em; text-transform: uppercase; color: var(--muted); margin-bottom: 8px; }
        .cart-lines { background: var(--bg); border: 1px solid var(--line); border-radius: 12px; overflow: hidden; }
        .cart-line { display: flex; gap: 12px; padding: 14px; align-items: flex-start; }
        .cart-line + .cart-line { border-top: 1px solid var(--line); }
        .cart-line-photo { width: 56px; height: 56px; flex-shrink: 0; border-radius: 8px; object-fit: cover; background: var(--soft); }
        .cart-line-body { flex: 1; min-width: 0; }
        .cart-line-name { font-size: 14px; font-weight: 600; letter-spacing: -0.005em; line-height: 1.3; }
        .cart-line-each { font-size: 12px; color: var(--muted); margin-top: 2px; }
        .cart-line-foot { margin-top: 10px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
        .cart-line-total { font-size: 14px; font-weight: 600; font-variant-numeric: tabular-nums; }
        .cart-summary { margin-top: 16px; padding: 14px; background: var(--bg); border: 1px solid var(--line); border-radius: 12px; }
        .cart-total { display: flex; justify-content: space-between; align-items: center; font-size: 15px; font-weight: 700; }
        .cart-total span:last-child { font-variant-numeric: tabular-nums; }
        .place { width: 100%; border: 0; cursor: pointer; border-radius: 12px; background: var(--accent); color: var(--accent-ink); padding: 15px 18px; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .place.split { justify-content: space-between; }
        .place[disabled] { opacity: .6; cursor: progress; }
        .cart-error { margin: 10px 0 0; font-size: 13px; color: #b3261e; }
        .cart-empty { padding: 60px 0; text-align: center; color: var(--muted); }

        /* ---- Cart sheet: its own screen on a phone, as the design has it ---- */
        dialog.sheet { border: 0; padding: 0; margin: 0; width: 100%; max-width: 100%; height: 100%; max-height: 100%; background: var(--page); color: var(--text); }
        dialog.sheet::backdrop { background: rgba(0,0,0,.45); }
        dialog.sheet[open] { animation: sheet-in .32s cubic-bezier(.2,.9,.3,1); }
        dialog.sheet[open]::backdrop { animation: fade-in .32s ease; }
        /* The cart script holds the dialog open through this before closing it. */
        dialog.sheet.closing { animation: sheet-out .22s cubic-bezier(.4,0,1,1) forwards; }
        dialog.sheet.closing::backdrop { animation: fade-in .22s ease reverse forwards; }
        .sheet-body { display: flex; flex-direction: column; height: 100%; }
        .sheet-head { display: flex; align-items: center; gap: 10px; padding: 12px 14px; background: var(--bg); border-bottom: 1px solid var(--line); }
        .sheet-head h2 { margin: 0 auto; font-size: 15px; font-weight: 600; }
        .sheet-close { width: 36px; height: 36px; flex-shrink: 0; border: 1px solid var(--line); border-radius: 999px; background: var(--bg); color: var(--text); cursor: pointer; display: flex; align-items: center; justify-content: center; }
        .sheet-scroll { flex: 1; overflow-y: auto; padding: 16px 16px 32px; }
        .sheet-foot { padding: 14px 14px calc(14px + env(safe-area-inset-bottom)); background: var(--bg); border-top: 1px solid var(--line); }
        .sheet-foot:empty { display: none; }

        /* ---- Dock: a floating row of actions ---- */
        .dock {
            position: fixed;
            inset-inline: 0;
            bottom: calc(14px + env(safe-area-inset-bottom));
            z-index: 30;
            padding: 0 14px;
            pointer-events: none;
        }
        body.has-dock { --dock: 76px; }
        .dock-inner {
            pointer-events: auto;
            /* Hugs its icons rather than stretching, so the pill is never
               mostly empty. Nothing expands, so its width never changes. */
            width: fit-content;
            max-width: 100%;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px;
            background: var(--bg);
            border: 1px solid var(--line);
            border-radius: 999px;
            /* Three layers: a hairline to seat it, a close shadow for shape,
               and a wide one to lift it off whatever it is floating over. */
            box-shadow:
                0 1px 2px rgba(0,0,0,.07),
                0 6px 16px rgba(0,0,0,.13),
                0 18px 42px rgba(0,0,0,.17);
        }

        .dockitem {
            flex: 0 0 auto;
            width: 46px;
            height: 46px;
            position: relative;
            /* What this action turns when it is taken. Each one borrows the
               colour its destination is already known by; only Back to top,
               which goes nowhere, falls back to the owner's own. */
            --tint: var(--accent);
        }
        .is-share { --tint: #7C3AED; }
        .is-lang { --tint: #0EA5E9; }
        .is-map { --tint: #EA4335; }
        .is-whatsapp { --tint: #25D366; }
        .is-social { --tint: #E1306C; }

        .dockface {
            width: 100%;
            height: 100%;
            border: 0;
            border-radius: 999px;
            background: var(--soft);
            color: var(--text);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            list-style: none;
            transition: color .2s ease, transform .12s ease;
        }
        .dockface::-webkit-details-marker { display: none; }
        .dockface .icon { width: 21px; height: 21px; }
        .dockitem:active > .dockface { transform: scale(.92); }

        /* The label names the button for a screen reader; on screen the icon
           speaks for itself, and taking it open only tints that icon. */
        .dock-label {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            padding: 0;
            border: 0;
            overflow: hidden;
            white-space: nowrap;
            clip-path: inset(50%);
        }

        .dockitem.is-on > .dockface,
        .dockitem:active > .dockface,
        .dockitem:has(:focus-visible) > .dockface { color: var(--tint); }

        /* ---- Popups: every dock action opens one, so they share a shape ---- */
        dialog.pop { border: 0; padding: 0; background: transparent; max-width: 330px; width: calc(100% - 32px); margin: auto; }
        dialog.pop::backdrop { background: rgba(0,0,0,.5); }
        dialog.pop[open] { animation: pop-in .2s cubic-bezier(.2,.9,.3,1); }
        dialog.pop[open]::backdrop { animation: fade-in .2s ease; }
        .pop-body { background: var(--bg); color: var(--text); border-radius: 18px; padding: 22px; text-align: center; }
        .pop-body h2 { margin: 0 0 6px; font-size: 16px; font-weight: 700; letter-spacing: -0.01em; }
        .pop-icon { display: flex; align-items: center; justify-content: center; width: 46px; height: 46px; margin: 0 auto 12px; border-radius: 999px; background: var(--soft); color: var(--accent); }
        .pop-icon svg { width: 23px; height: 23px; }
        .pop-icon.is-whatsapp-ink { color: #25D366; }
        .pop-icon.is-map-ink { color: #EA4335; }
        .pop-map { display: block; width: 100%; height: 170px; margin-bottom: 16px; border: 0; border-radius: 12px; background: var(--soft); }
        .pop-note { margin: 0; font-size: 13px; color: var(--muted); line-height: 1.5; word-break: break-word; }
        .pop-go {
            display: block;
            margin-top: 16px;
            border-radius: 12px;
            background: var(--accent);
            color: var(--accent-ink);
            padding: 13px;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
        }
        .pop-go.is-whatsapp-go { background: #25D366; color: #fff; }
        .pop-done { width: 100%; margin-top: 8px; border: 0; cursor: pointer; border-radius: 12px; background: transparent; color: var(--muted); padding: 12px; font-size: 14px; font-weight: 600; }

        .pop-list { display: flex; flex-direction: column; gap: 4px; margin-top: 14px; }
        .pop-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            font-size: 14px;
            color: inherit;
            text-decoration: none;
        }
        .pop-item .icon { width: 20px; height: 20px; }
        /* Under WhatsApp, a rule and a small label mark where the links start. */
        .pop-subhead {
            margin: 20px 0 0;
            padding-top: 16px;
            border-top: 1px solid var(--line);
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .06em;
            text-transform: uppercase;
            color: var(--muted);
        }
        .pop-subhead + .pop-list { margin-top: 10px; }
        /* Each network in its own colour; X and TikTok are black-on-white brands. */
        .pop-item.is-instagram .icon { color: #E1306C; }
        .pop-item.is-facebook .icon { color: #1877F2; }
        .pop-item.is-x .icon, .pop-item.is-tiktok .icon { color: var(--text); }
        .pop-item[aria-current="true"] { border-color: var(--accent); background: color-mix(in srgb, var(--accent) 8%, var(--bg)); font-weight: 600; }

        .qr-code { display: flex; justify-content: center; min-height: 196px; margin-top: 14px; }
        .qr-code svg { width: 196px; height: 196px; shape-rendering: crispEdges; }
        .qr-code + .pop-note { margin-top: 12px; font-size: 12px; }

        /* The header copy, which only a wide screen shows. Its menu drops down
           rather than up, and hangs off the end of the bar. */
        .topbar-cart {
            margin-inline-start: auto;
            position: relative;
            width: 36px;
            height: 36px;
            flex-shrink: 0;
            border: 1px solid var(--line);
            border-radius: 999px;
            background: var(--bg);
            color: var(--text);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform .12s ease;
        }
        .topbar-cart:active { transform: scale(.94); }
        .topbar-cart .badge {
            position: absolute;
            top: -2px;
            inset-inline-end: -2px;
            min-width: 16px;
            height: 16px;
            padding: 0 4px;
            border-radius: 999px;
            background: var(--accent);
            color: var(--accent-ink);
            font-size: 9px;
            font-weight: 700;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .topbar-cart.on .badge { display: flex; }

        /* Round buttons in the wide-screen header, standing in for the dock. */
        .top-action { display: none; flex-shrink: 0; min-width: 0; width: 36px; height: 36px; padding: 0; background: var(--bg); border: 1px solid var(--line); }

        /* ---- Motion ---- */
        @keyframes sheet-in { from { transform: translateY(100%); } to { transform: none; } }
        @keyframes sheet-out { from { transform: none; } to { transform: translateY(100%); } }
        @keyframes fade-in { from { opacity: 0; } to { opacity: 1; } }
        @keyframes pop-in { from { transform: scale(.6); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        @keyframes count-pop { 0%, 100% { transform: scale(1); } 45% { transform: scale(1.32); } }

        .dish-action > .enter { animation: pop-in .22s cubic-bezier(.2,.9,.3,1); }
        .pop { animation: count-pop .34s ease; }

        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after, *::backdrop {
                animation-duration: .01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: .01ms !important;
            }
        }

        /* ---- Chrome ---- */
        .empty { padding: 60px 16px; text-align: center; color: var(--muted); }
        .no-results { display: none; padding: 40px 16px; text-align: center; color: var(--muted); font-size: 13.5px; }

        /* ---- Wide: the header carries the facts, the cart is its own column ---- */
        @media (min-width: 1024px) {
            html, body { height: 100%; }
            body { display: flex; flex-direction: column; overflow: hidden; }

            .topbar { flex: none; position: static; }
            .topbar-inner { padding: 14px 32px; gap: 24px; }
            .brand-mark { width: 28px; height: 28px; font-size: 14px; }
            .brand-name { font-size: 16px; }
            .facts-row { display: flex; }
            .field.topbar-search { display: flex; min-width: 220px; padding: 8px 14px; border-radius: 8px; }
            .top-action { display: flex; }
            .field.topbar-search input { font-size: 13px; }
            /* The cart has its own column here, so the header button and the
               full-screen sheet it opens are phone-only. */
            .info, .search, .dock, .topbar-cart, dialog.sheet { display: none !important; }

            .wrap { flex: 1; min-height: 0; display: grid; grid-template-columns: minmax(0, 1fr); overflow: hidden; }
            .wrap.with-cart { grid-template-columns: minmax(0, 1fr) 360px; }
            .col-main { overflow-y: auto; scroll-behavior: smooth; }

            .cover { margin: 24px 32px 0; height: 200px; border-radius: 14px; }
            .cover-body { padding: 28px 32px; }
            .cover-body h1 { font-size: 36px; }
            .cover-body p { margin-top: 8px; font-size: 15px; }

            .tabs { top: 0; margin-top: 18px; padding: 14px 32px; }
            .tabs-inner { padding: 0; }

            .sections { padding-bottom: 60px; }
            .category { scroll-margin-top: 72px; }
            .category-head { padding: 22px 32px 10px; }
            .dishes { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 0 32px; }

            .col-aside { background: var(--bg); border-inline-start: 1px solid var(--line); padding: 24px; overflow-y: auto; }
            .col-aside > h2 { margin: 0 0 18px; font-size: 18px; font-weight: 700; letter-spacing: -0.01em; }
            .col-aside .cart-lines { border: 0; border-radius: 0; }
            .col-aside .cart-line { padding: 14px 0; border-top: 0; border-bottom: 1px solid var(--line); }
            .col-aside .cart-line-photo { width: 48px; height: 48px; }
            .col-aside .cart-summary { background: transparent; border: 0; padding: 0; }
            .col-aside .place { margin-top: 14px; padding: 13px; border-radius: 10px; }
        }
        @media (max-width: 1023px) { .col-aside { display: none; } }
    </style>
</head>
<body @class(['has-dock' => $hasBottomBar])>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="#top">
            @if ($logo)
                <img class="brand-mark" src="{{ $logo }}" alt="">
            @else
                <span class="brand-mark">{{ Str::upper(Str::substr($name, 0, 1)) }}</span>
            @endif
            <span class="brand-name">{{ $name }}</span>
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
                 reached from here, through the same popup. --}}
            <button type="button" class="top-action dockface" data-pop-open="contact" aria-haspopup="dialog"
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

    </div>
</header>

<div class="wrap {{ $can_order ? 'with-cart' : '' }}" id="top">
    <main class="col-main">
        <div class="cover">
            @if ($cover)
                <img class="cover-photo" src="{{ $cover }}" alt="" loading="lazy">
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
                                $image = $dish->getFirstMediaUrl('image') ?: null;
                                $ingredients = $text($dish, 'ingredients');
                                $dishName = $text($dish, 'name');
                            @endphp
                            <article class="dish" data-dish="{{ $dish->id }}" data-name="{{ $dishName }}"
                                     data-image="{{ $image }}"
                                     data-price="{{ $dish->price !== null ? (string) $dish->price : '' }}"
                                     data-search="{{ Str::lower($dishName.' '.$ingredients) }}">
                                @if ($image)
                                    <img class="dish-photo" src="{{ $image }}" alt="{{ $dishName }}" loading="lazy" decoding="async">
                                @endif
                                <div class="dish-body">
                                    <span class="dish-name">{{ $dishName }}</span>
                                    @if ($ingredients)
                                        <p class="ingredients">{{ $ingredients }}</p>
                                    @endif
                                    <div class="dish-foot">
                                        @if ($dish->price !== null)
                                            <span class="price">{{ $currency }}{{ number_format((float) $dish->price, 2) }}</span>
                                        @else
                                            <span></span>
                                        @endif
                                        @if ($can_order && $dish->price !== null)
                                            <span class="dish-action"></span>
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

        </div>
    </main>

    @if ($can_order)
        <aside class="col-aside">
            <h2>{{ __('Your cart') }}</h2>
            <div data-cart-panel="desktop"></div>
        </aside>
    @endif
</div>

@if ($hasBottomBar)
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

            @if ($hasContact)
                {{-- WhatsApp and the social links share one item — they are the
                     same job, reaching the restaurant. Without WhatsApp the
                     item wears the heart and opens the links alone. --}}
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
            {{-- The generator is 56 KB, so it is fetched the first time this
                 opens rather than on every menu render. --}}
            <div class="qr-code" data-qr-canvas
                 data-url="{{ $menu_url }}"
                 data-lib="{{ asset('js/qrcode-generator.js') }}"></div>
            <p class="pop-note" dir="ltr">{{ $menu_url }}</p>
            <button type="button" class="pop-done" data-pop-close>{{ __('Close') }}</button>
        </div>
    </dialog>
@endif

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
                     frame send one — it restores openstreetmap.org's origin,
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
            icons: { plus: @js($icons['plus']), minus: @js($icons['minus']) },
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
            },
        };
    </script>
    <script src="{{ asset('js/menu-cart.js') }}" defer></script>
@endif

@unless ($is_preview)
    <script>
        window.QAYEMA_TRACK = { url: @js(route('public.events', $restaurant->slug)) };
    </script>
    <script src="{{ asset('js/menu-track.js') }}" defer></script>
@endunless
<script src="{{ asset('js/menu-nav.js') }}" defer></script>
</body>
</html>
