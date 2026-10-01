{{-- The closing call to action on the topic, pricing and guide pages. --}}
@php
    $owner = auth()->user();
    $href = $owner?->afterLoginUrl() ?? route('register');
    $label = $owner
        ? ($owner->hasCompletedOnboarding() ? __('portal.nav.cta_dashboard') : __('portal.nav.cta_continue'))
        : __('pages.cta.button');
@endphp
<section class="sec page-cta">
  <div class="wrap">
    <div class="page-cta-card reveal">
      <h2 class="display">{{ __('pages.cta.title') }}</h2>
      <p>{{ __('pages.cta.body') }}</p>
      <div class="page-cta-actions">
        <a class="btn btn-gold" data-magnetic href="{{ $href }}">{{ $label }}</a>
        @if (($pricingLink ?? true) && \App\Support\PortalUrl::current() !== 'pricing')
          <a class="btn btn-line" href="{{ \App\Support\PortalUrl::to('pricing') }}">{{ __('pages.cta.secondary') }}</a>
        @endif
      </div>
    </div>
  </div>
</section>
