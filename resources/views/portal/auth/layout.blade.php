{{--
    Shared chrome for the guest auth pages (login, forgot password, reset
    password): brand bar with theme + language pickers on the left, cover
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
    $seoTitle  = ($seoTitle ?? __('auth.login.eyebrow')).' — '.$appName;
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('portal/css/components/ui.css') }}?v={{ @filemtime(public_path('portal/css/components/ui.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('portal/css/pages/login.css') }}?v={{ @filemtime(public_path('portal/css/pages/login.css')) ?: '1' }}">
@endpush

@section('content')
<div class="login" x-data="{ showPass: false }">

    {{-- ── Left: form column ─────────────────────────────── --}}
    <section class="form-col" dir="{{ $dir }}">

        <div class="form-top">
            <a class="brand" href="{{ url('/') }}">
                <img src="{{ asset('images/logo/logo.png') }}" alt="{{ $appName }}">
            </a>

            <div class="top-actions">
            <button class="theme-tog" type="button" aria-label="theme">
                <span class="knob">
                    <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    <svg class="moon" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                </span>
            </button>

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
                <a href="{{ route('privacy') }}">{{ __('auth.login.privacy') }}</a>
                <span class="dot-sep">·</span>
                <a href="{{ route('terms') }}">{{ __('auth.login.terms') }}</a>
            </span>
        </div>
    </section>

    {{-- ── Right: cover image ───────────────────────────── --}}
    <aside class="brand-col">
        <img class="brand-image" src="{{ asset('images/auth.png') }}" alt="" onerror="this.style.display='none'">
    </aside>

</div>
@endsection
