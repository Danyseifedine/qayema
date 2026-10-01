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
      <button class="theme-tog" id="themeTog" aria-label="theme">
        <span class="knob">
          <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
          <svg class="moon" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
        </span>
      </button>
      {{-- Logged-in owners get a way into the app (register is guest-only);
           guests get the sign-up CTA. --}}
      @auth
        @if (auth()->user()->hasCompletedOnboarding())
          <a class="btn btn-gold btn-sm" data-magnetic href="{{ config('app.dashboard_url') }}">{{ __('portal.nav.cta_dashboard') }}</a>
        @else
          <a class="btn btn-gold btn-sm" data-magnetic href="{{ route('onboarding') }}">{{ __('portal.nav.cta_continue') }}</a>
        @endif
      @else
        {{-- The narrowest phones show data-short instead (navbar.css). --}}
        <a class="btn btn-gold btn-sm nav-cta" data-magnetic href="{{ route('register') }}" data-short="{{ __('portal.nav.cta_short') }}" aria-label="{{ __('portal.nav.cta') }}"><span class="cta-full">{{ __('portal.nav.cta') }}</span></a>
      @endauth
    </div>
  </div>
</nav>
<span id="top"></span>
