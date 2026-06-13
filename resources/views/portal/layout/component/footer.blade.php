{{-- ===== Portal footer ===== --}}
@php
    // Section links jump home first when viewed from a non-home portal page.
    $base = request()->is('/') ? '' : '/';
    $locale = app()->getLocale();
@endphp
<footer>
  <div class="wrap">
    <div class="foot-top">
      <div class="foot-brand">
        <img src="{{ asset('images/logo/logo.svg') }}" alt="Qayema"/>
        <p>{{ __('portal.footer.tagline') }}</p>
        <div class="seg lang" role="group" aria-label="language">
          <a href="{{ route('locale.switch', 'ar') }}" class="{{ $locale === 'ar' ? 'on' : '' }}">ع</a>
          <a href="{{ route('locale.switch', 'en') }}" class="{{ $locale === 'en' ? 'on' : '' }}">EN</a>
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
        <a href="#">{{ __('portal.footer.about') }}</a>
        <a href="{{ route('contact') }}">{{ __('portal.footer.contact') }}</a>
        <a href="{{ $base }}#stories">{{ __('portal.footer.stories') }}</a>
      </div>
      <div class="foot-col">
        <h5>{{ __('portal.footer.legal') }}</h5>
        <a href="{{ route('terms') }}">{{ __('portal.footer.terms') }}</a>
        <a href="{{ route('privacy') }}">{{ __('portal.footer.privacy') }}</a>
        <a href="{{ route('cookies') }}">{{ __('portal.footer.cookies') }}</a>
        <a href="{{ route('refund') }}">{{ __('portal.footer.refund') }}</a>
      </div>
    </div>
    <div class="foot-bot">
      <span class="cp">{{ __('portal.footer.copyright') }}</span>
      <div class="foot-soc">
        <a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r=".8" fill="currentColor"/></svg></a>
        <a href="#" aria-label="X"><svg viewBox="0 0 24 24" fill="currentColor"><path d="M18.9 2H22l-7.1 8.1L23 22h-6.8l-5-6.6L5.5 22H2.3l7.6-8.7L1.5 2h6.9l4.5 6 5.9-6zm-1.2 18h1.9L7.4 4H5.4l12.3 16z"/></svg></a>
        <a href="#" aria-label="WhatsApp"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M3 21l1.7-5A8 8 0 1 1 8 19.3z"/></svg></a>
      </div>
    </div>
  </div>
</footer>
