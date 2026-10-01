@php
    $design = $card['design'];
    $isRtl = in_array(app()->getLocale(), config('locales.rtl', ['ar']), true);
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="robots" content="noindex, nofollow" />
<title>{{ $design['title'] ?: config('app.name') }} | QR</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="{{ $card['fonts_href'] }}" rel="stylesheet">
<style>
    /* The table card, in the menu's own look: the same type and the owner's
       accent, so the card on the table matches the menu it opens. */
    :root {
        --accent: {{ $card['accent'] }};
        --accent-ink: {{ $card['accent_ink'] }};
        {{-- The owner's fonts (MenuFonts); raw because {{ }} would escape the quotes. --}}
        --font: {!! $card['font'] !!}, system-ui, sans-serif;
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        min-height: 100vh;
        display: grid;
        place-items: center;
        gap: 18px;
        padding: 32px 16px;
        background: #F4F5F7;
        font-family: var(--font);
        -webkit-font-smoothing: antialiased;
    }

    .card {
        display: block;
        width: 320px;
        padding: 30px 26px 26px;
        border-radius: 22px;
        text-align: center;
        text-decoration: none;
        /* The card is a fixed width, so a long word (a name, a link, a row
           of letters) breaks inside it rather than running off the edge. */
        overflow-wrap: anywhere;
        box-shadow: 0 1px 2px rgba(0,0,0,.06), 0 18px 44px rgba(0,0,0,.12);
    }
    .card.theme-light { background: #FFFFFF; color: #111418; border: 1px solid #E5E7EB; --muted: #6B7280; }
    .card.theme-dark  { background: #111418; color: #FFFFFF; --muted: rgba(255,255,255,.62); }
    .card.theme-brand { background: var(--accent); color: var(--accent-ink); --muted: color-mix(in srgb, var(--accent-ink) 70%, transparent); }

    .title { font-size: 22px; font-weight: 700; letter-spacing: -0.02em; line-height: 1.15; }
    .subtitle { margin-top: 5px; font-size: 13px; font-weight: 500; color: var(--muted); }

    /* The code sits on its own background, so a dark or brand card never
       changes how it scans. */
    .frame {
        display: inline-block;
        margin-top: 22px;
        padding: 14px;
        border-radius: 16px;
        background: {{ $design['background'] }};
    }
    .frame svg, .frame canvas { display: block; width: 196px; height: 196px; }

    .cta { margin-top: 20px; font-size: 14px; font-weight: 600; }
    .url { margin-top: 6px; font-size: 12px; color: var(--muted); direction: ltr; unicode-bidi: isolate; }

    .print {
        border: 1px solid #E5E7EB;
        background: #FFFFFF;
        color: #111418;
        font: inherit;
        font-size: 13px;
        font-weight: 600;
        padding: 9px 16px;
        border-radius: 10px;
        cursor: pointer;
    }

    @media print {
        body { background: #FFFFFF; padding: 0; }
        .card { box-shadow: none; }
        .print { display: none; }
        /* Browsers drop backgrounds when printing unless told otherwise. */
        .card, .frame { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    }
</style>
</head>
<body>
    <a class="card theme-{{ $design['card_theme'] }}" href="{{ $card['url'] }}">
        <div class="title">{{ $design['title'] ?: config('app.name') }}</div>
        @if ($design['subtitle'])
            <div class="subtitle">{{ $design['subtitle'] }}</div>
        @endif

        <div class="frame"><div id="qr" role="img" aria-label="{{ __('QR code for the menu') }}"></div></div>

        <div class="cta">{{ $design['cta'] ?: __('Scan to see the menu') }}</div>
        @if ($design['show_url'])
            <div class="url">{{ $card['display_url'] }}</div>
        @endif
    </a>

    <button type="button" class="print" onclick="window.print()">{{ __('Print') }}</button>

    <script src="{{ asset('js/qr-code-styling.js') }}"></script>
    <script>
        (function () {
            // The same library and the same options the dashboard previews
            // with (built by App\Services\Qr\QrStyle), so what the owner
            // designed is what gets printed. SVG keeps it sharp on paper.
            var options = @json($card['options']);
            options.width = 196;
            options.height = 196;
            options.type = 'svg';

            new QRCodeStyling(options).append(document.getElementById('qr'));
        })();
    </script>
</body>
</html>
