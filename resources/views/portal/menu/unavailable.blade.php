<!DOCTYPE html>
@php
    $locale = $restaurant->default_locale ?? 'ar';
    $isRtl = $locale === 'ar';
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>{{ $restaurant->name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600&family=Noto+Kufi+Arabic:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: {{ $isRtl ? "'Noto Kufi Arabic'" : "'Geist'" }}, sans-serif;
            background: #0F0F10; color: #F6F1E8; min-height: 100vh;
            display: flex; align-items: center; justify-content: center; padding: 24px;
        }
        .card { max-width: 420px; text-align: center; }
        .ring {
            width: 72px; height: 72px; border-radius: 50%; margin: 0 auto 28px;
            background: rgba(200,168,90,.12); border: 1px solid rgba(200,168,90,.3);
            display: flex; align-items: center; justify-content: center; color: #C8A85A;
        }
        h1 { font-size: 22px; font-weight: 600; margin-bottom: 12px; }
        p { font-size: 14.5px; line-height: 1.7; color: rgba(246,241,232,.55); }
        .name { color: #C8A85A; font-weight: 600; }
    </style>
</head>
<body>
    <div class="card">
        <div class="ring">
            <svg width="30" height="30" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
            </svg>
        </div>
        @if ($isRtl)
            <h1>القائمة غير متاحة مؤقتاً</h1>
            <p>قائمة <span class="name">{{ $restaurant->name }}</span> غير متاحة في الوقت الحالي. يرجى المحاولة مرة أخرى لاحقاً.</p>
        @else
            <h1>Menu temporarily unavailable</h1>
            <p>The menu for <span class="name">{{ $restaurant->name }}</span> is not available right now. Please check back soon.</p>
        @endif
    </div>
</body>
</html>
