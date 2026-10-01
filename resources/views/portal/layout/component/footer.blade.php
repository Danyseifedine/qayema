{{-- ===== Portal footer ===== --}}
@use('App\Support\PortalUrl')
@php
    // Section links jump to home in this language first from any other page.
    $base = PortalUrl::current() === 'home' ? '' : PortalUrl::to('home');
    $locale = app()->getLocale();
@endphp
<footer>
  <div class="wrap">
    <div class="foot-top">
      <div class="foot-brand">
        <img src="{{ asset('images/logo/logo.svg') }}" alt="Qayema"/>
        <p>{{ __('portal.footer.tagline') }}</p>
        <div class="seg lang" role="group" aria-label="language">
          <a href="{{ PortalUrl::switchTo('ar') }}" hreflang="ar" lang="ar" class="{{ $locale === 'ar' ? 'on' : '' }}">ع</a>
          <a href="{{ PortalUrl::switchTo('en') }}" hreflang="en" lang="en" class="{{ $locale === 'en' ? 'on' : '' }}">EN</a>
        </div>
      </div>
      <div class="foot-col">
        <h5>{{ __('portal.footer.product') }}</h5>
        <a href="{{ $base }}#features">{{ __('portal.nav.features') }}</a>
        <a href="{{ $base }}#pricing">{{ __('portal.nav.pricing') }}</a>
        <a href="{{ $base }}#how">{{ __('portal.nav.how') }}</a>
      </div>
      <div class="foot-col">
        <h5>{{ __('portal.footer.company') }}</h5>
        <a href="{{ PortalUrl::to('contact') }}">{{ __('portal.footer.contact') }}</a>
        <a href="{{ $base }}#faq">{{ __('portal.nav.faq') }}</a>
      </div>
      <div class="foot-col">
        <h5>{{ __('portal.footer.legal') }}</h5>
        <a href="{{ PortalUrl::to('terms') }}">{{ __('portal.footer.terms') }}</a>
        <a href="{{ PortalUrl::to('privacy') }}">{{ __('portal.footer.privacy') }}</a>
        <a href="{{ PortalUrl::to('cookies') }}">{{ __('portal.footer.cookies') }}</a>
        <a href="{{ PortalUrl::to('refund') }}">{{ __('portal.footer.refund') }}</a>
      </div>
    </div>
    <div class="foot-bot">
      <span class="cp">{{ __('portal.footer.copyright') }}</span>
    </div>
  </div>
</footer>
