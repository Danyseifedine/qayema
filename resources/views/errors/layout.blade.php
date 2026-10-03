{{--
    Every error page (404, 500, …) is a page of the site: the navbar, a hero
    like the content pages' and the footer. Each code's view passes $code and
    $key, the entry in lang/{locale}/errors.php.

    Arabic when the address is Arabic (/ar…, ?lang=ar), English otherwise.
    A server error may come from the database itself, so on one the navbar
    shows its guest version without asking who is signed in ($safeChrome).
--}}
@extends('portal.layout.master.master')
@use('App\Support\PortalUrl')
@use('App\View\Components\Seo')

@php
    $locale = request()->is('ar', 'ar/*') || request()->query('lang') === 'ar' ? 'ar' : 'en';
    app()->setLocale($locale);
    $page = __("errors.{$key}");
    $retry = in_array($key, ['419', '429', '500', '503', '5xx'], true);
    $safeChrome = (int) $code >= 500;
    $seoTitle = $page['title'].' '.$page['gold'];
    $seoDescription = $page['message'];
    $seoRobots = Seo::NOINDEX;
    $home = PortalUrl::to('home', $locale);
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('portal/css/pages/content.css') }}?v={{ @filemtime(public_path('portal/css/pages/content.css')) ?: '1' }}">
<style>
    /* Room enough that the footer never rides up under a short hero. */
    .error-hero { min-height: calc(100dvh - 160px); display: flex; align-items: center; box-sizing: border-box; }
    .error-hero .wrap { width: 100%; }
    .error-hero .btn svg { width: 16px; height: 16px; }
    [dir="rtl"] .error-hero .btn svg { transform: scaleX(-1); }
</style>
@endpush

@section('content')
  <header class="page-hero error-hero">
    <div class="wrap">
      <div class="eyebrow"><span class="bar"></span><span class="mono-label">{{ __('errors.eyebrow', ['code' => $code]) }}</span><span class="bar"></span></div>
      <h1 class="display">{{ $page['title'] }} <span class="gold-text">{{ $page['gold'] }}</span></h1>
      <p class="lede">{{ $page['message'] }}</p>
      <div class="actions">
        @if ($retry)
          <button type="button" class="btn btn-gold" data-magnetic onclick="location.reload()">{{ __('errors.try_again') }}</button>
          <a class="btn btn-line" href="{{ $home }}">{{ __('errors.back_home') }}</a>
        @else
          <a class="btn btn-gold" data-magnetic href="{{ $home }}">
            {{ __('errors.back_home') }}
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
          </a>
          <a class="btn btn-line" href="{{ PortalUrl::to('pricing', $locale) }}">{{ __('errors.pricing') }}</a>
        @endif
      </div>
    </div>
  </header>
@endsection
