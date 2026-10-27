<!DOCTYPE html>
@php
    $bare = $bare ?? false;
    $locale = app()->getLocale();
    $isRtl = in_array($locale, config('locales.rtl', []), true);
@endphp
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />

    <x-seo :title="$seoTitle ?? __('portal.seo.title')" :description="$seoDescription ?? __('portal.seo.description')" :robots="$seoRobots ?? null" :schema="$seoSchema ?? []" />

    <link
        href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&family=El+Messiri:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ \App\Support\Asset::versioned('portal/css/app.css') }}">
    @unless ($bare)
        <link rel="stylesheet" href="{{ \App\Support\Asset::versioned('portal/css/pages/landing.css') }}">
        <link rel="stylesheet" href="{{ \App\Support\Asset::versioned('portal/css/layout/navbar.css') }}">
        <link rel="stylesheet" href="{{ \App\Support\Asset::versioned('portal/css/layout/footer.css') }}">
    @endunless
    @stack('styles')
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
        <script src="{{ \App\Support\Asset::versioned('portal/js/landing.js') }}"></script>
    @else
        {{-- Bare pages (login, onboarding) are Alpine apps, not GSAP marketing pages --}}
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.17.4/dist/cdn.min.js"></script>
    @endunless
    @stack('scripts')
</body>

</html>
