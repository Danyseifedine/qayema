{{-- ===== Portal navbar ===== --}}
@use('App\Support\PortalUrl')
@php
    // On the home page, section links are same-page anchors (Lenis smooth-scrolls
    // them). On any other page they jump to home in this language first.
    $base = PortalUrl::current() === 'home' ? '' : PortalUrl::to('home');
    $locale = app()->getLocale();
@endphp
<nav class="nav">
  <div class="nav-in">
    <a class="brand" href="{{ $base === '' ? '#top' : PortalUrl::to('home') }}">
        <img src="{{ asset('images/logo/logo.svg') }}" alt="Qayema" />
    </a>
    <div class="nav-links">
      <a href="{{ $base }}#features">{{ __('portal.nav.features') }}</a>
      <a href="{{ $base }}#how">{{ __('portal.nav.how') }}</a>
      <a href="{{ $base }}#pricing">{{ __('portal.nav.pricing') }}</a>
      <a href="{{ $base }}#faq">{{ __('portal.nav.faq') }}</a>
    </div>
    <div class="nav-right">
      {{-- language switch: the same page in the other language --}}
      <div class="seg lang" role="group" aria-label="language">
        <a href="{{ PortalUrl::switchTo('ar') }}" hreflang="ar" lang="ar" class="{{ $locale === 'ar' ? 'on' : '' }}">ع</a>
        <a href="{{ PortalUrl::switchTo('en') }}" hreflang="en" lang="en" class="{{ $locale === 'en' ? 'on' : '' }}">EN</a>
      </div>
      {{-- Logged-in owners get a way into the app (register is guest-only);
           guests get the sign-up CTA. A server error page ($safeChrome) does
           not ask who is signed in: the database may be what failed. --}}
      @if (! ($safeChrome ?? false) && auth()->check())
        @if (auth()->user()->hasCompletedOnboarding())
          <a class="btn btn-gold btn-sm" data-magnetic href="{{ config('app.dashboard_url') }}">{{ __('portal.nav.cta_dashboard') }}</a>
        @else
          <a class="btn btn-gold btn-sm" data-magnetic href="{{ route('onboarding') }}">{{ __('portal.nav.cta_continue') }}</a>
        @endif
      @else
        {{-- The narrowest phones show data-short instead (navbar.css). --}}
        <a class="btn btn-gold btn-sm nav-cta" data-magnetic href="{{ route('register') }}" data-short="{{ __('portal.nav.cta_short') }}" aria-label="{{ __('portal.nav.cta') }}"><span class="cta-full">{{ __('portal.nav.cta') }}</span></a>
      @endif
    </div>
  </div>
</nav>
<span id="top"></span>
