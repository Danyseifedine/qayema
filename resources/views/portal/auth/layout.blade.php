{{--
    Shared chrome for the guest auth pages (login, forgot password, reset
    password): brand bar with the language picker on the left, cover
    image on the right. Pages fill @section('eyebrow'), 'title', 'subtitle'
    and 'form'.
--}}
@extends('portal.layout.master.master')

@php
    $bare = true;
    $locale    = app()->getLocale();
    $isRtl     = in_array($locale, config('locales.rtl', ['ar']));
    $dir       = $isRtl ? 'rtl' : 'ltr';
    $appName   = config('app.name', 'Qayema');
    $locales   = config('locales.locales');
    $currentLocale = $locales[$locale] ?? $locales['en'];
    $seoTitle  = $seoTitle ?? __('auth.login.eyebrow');
    // Sign-in and password pages stay out of search results.
    $seoRobots = \App\View\Components\Seo::NOINDEX;
@endphp

@push('styles')
<link rel="stylesheet" href="{{ \App\Support\Asset::versioned('portal/css/components/ui.css') }}">
<link rel="stylesheet" href="{{ \App\Support\Asset::versioned('portal/css/pages/login.css') }}">
@endpush

@section('content')
<div class="login">

    {{-- ── Left: form column ─────────────────────────────── --}}
    <section class="form-col" dir="{{ $dir }}">

        <div class="form-top">
            <a class="brand" href="{{ url('/') }}">
                <img src="{{ asset('images/logo/logo.png') }}" alt="{{ $appName }}">
            </a>

            {{-- Server-side language picker --}}
            <div class="lang-picker" x-data="{ open: false }" @click.outside="open = false">
                <button class="lang-trigger" @click="open = !open" :aria-expanded="open" type="button">
                    <span>{{ $currentLocale['flag'] }}</span>
                    <span>{{ strtoupper($locale) }}</span>
                    <svg class="lang-chevron" :class="open ? 'open' : ''"
                         width="12" height="12" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2" stroke-linecap="round">
                        <path d="M6 9l6 6 6-6"/>
                    </svg>
                </button>
                <div class="lang-dropdown" x-show="open" x-cloak
                     x-transition:enter="lang-drop-enter"
                     x-transition:enter-start="lang-drop-enter-start"
                     x-transition:enter-end="lang-drop-enter-end"
                     x-transition:leave="lang-drop-enter"
                     x-transition:leave-start="lang-drop-enter-end"
                     x-transition:leave-end="lang-drop-enter-start">
                    @foreach($locales as $code => $info)
                        <a class="lang-option {{ $locale === $code ? 'active' : '' }}"
                           href="{{ route('locale.switch', $code) }}">
                            <span>{{ $info['flag'] }}</span>
                            <span>{{ $info['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="form-body">

            <div class="eyebrow">
                <span class="dash"></span>
                <span>@yield('eyebrow')</span>
                <span class="dot"></span>
            </div>

            <h1 class="title">@yield('title')</h1>

            <p class="subtitle">@yield('subtitle')</p>

            @if (session('status'))
                <div class="alert alert-status">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            @yield('form')
        </div>

        <div class="form-foot">
            <span>© {{ date('Y') }} {{ $appName }}</span>
            <span>
                <a href="{{ \App\Support\PortalUrl::to('privacy') }}">{{ __('auth.login.privacy') }}</a>
                <span class="dot-sep">·</span>
                <a href="{{ \App\Support\PortalUrl::to('terms') }}">{{ __('auth.login.terms') }}</a>
            </span>
        </div>
    </section>

    {{-- ── Right: cover image ───────────────────────────── --}}
    <aside class="brand-col">
        <img class="brand-image" src="{{ asset('images/auth.png') }}" alt="" onerror="this.style.display='none'">
    </aside>

</div>
@endsection
