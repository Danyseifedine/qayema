<!DOCTYPE html>
@php
    $bare = $bare ?? false;
    $locale = app()->getLocale();
    $isRtl = in_array($locale, config('locales.rtl', []), true);
    // cache-bust static assets by file mtime so CSS/JS edits always take effect
    $ver = fn(string $path): string => asset($path) . '?v=' . (@filemtime(public_path($path)) ?: '1');
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}" data-theme="light">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <x-seo :title="$seoTitle ?? 'Qayema — Your restaurant menu, live with one QR'" :description="$seoDescription ??
        'Photograph your menu, let AI rebuild it bilingually in Arabic & English, and go live with one custom QR code. The Arabic-first digital menu platform.'" />

    <link
        href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&family=El+Messiri:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ $ver('portal/css/app.css') }}">
    @unless ($bare)
        <link rel="stylesheet" href="{{ $ver('portal/css/pages/landing.css') }}">
        <link rel="stylesheet" href="{{ $ver('portal/css/layout/navbar.css') }}">
        <link rel="stylesheet" href="{{ $ver('portal/css/layout/footer.css') }}">
    @endunless
    @stack('styles')
    @if ($bare)
        <link rel="stylesheet" href="{{ $ver('portal/css/components/auth-dark.css') }}">
    @endif

    <script>
        {{-- language + direction are server-rendered (session locale); only theme is client-side --}}
            (function() {
                try {
                    var t = localStorage.getItem('qayema-theme') || 'light';
                    document.documentElement.setAttribute('data-theme', t);
                } catch (e) {}
            })();
    </script>
</head>

<body style="font-family: var(--font-sans)">

    @unless ($bare)
        @include('portal.layout.component.navbar')
    @endunless

    @yield('content')

    @unless ($bare)
        @include('portal.layout.component.footer')
    @endunless

    @unless ($bare)
        {{-- GSAP + Lenis + landing choreography (shared by every chrome'd portal page) --}}
        <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/gsap.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/gsap@3.13.0/dist/ScrollTrigger.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@studio-freight/lenis@1.0.42/dist/lenis.min.js"></script>
        <script src="{{ $ver('portal/js/landing.js') }}"></script>
    @else
        {{-- Bare pages (login, onboarding) are Alpine apps, not GSAP marketing pages --}}
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
        <script>
            (function () {
                function wire() {
                    document.querySelectorAll('.theme-tog').forEach(function (b) {
                        b.addEventListener('click', function () {
                            var n = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                            document.documentElement.setAttribute('data-theme', n);
                            try { localStorage.setItem('qayema-theme', n); } catch (e) {}
                        });
                    });
                }
                if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', wire);
                else wire();
            })();
        </script>
    @endunless
    @stack('scripts')
</body>

</html>
