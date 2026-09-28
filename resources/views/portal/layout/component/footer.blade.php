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
        <a href="{{ route('contact') }}">{{ __('portal.footer.contact') }}</a>
        <a href="{{ $base }}#faq">{{ __('portal.nav.faq') }}</a>
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
    </div>
  </div>
</footer>
