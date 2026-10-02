{{-- The package cards, on home and on the pricing page. Prices come from the
     packages an admin edits, so they never promise what a package does not
     hold. $heading = false leaves out the section title (the pricing page
     has its own); $pricingCards reuses the page's PricingCards. --}}
@php
    $cards = $pricingCards ?? app(\App\Services\Portal\PricingCards::class);
    $pricing = $cards->all();
    $fairUse = $cards->fairUseNote();
    $owner = auth()->user();
    $ctaAuthedHref = $owner?->afterLoginUrl();
    $ctaAuthedLabel = $owner
        ? ($owner->hasCompletedOnboarding() ? __('portal.nav.cta_dashboard') : __('portal.nav.cta_continue'))
        : null;
    $check = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
@endphp
<section class="sec" id="pricing" style="padding-top:0;">
  <div class="wrap">
    @if ($heading ?? true)
      <div class="sec-head reveal" style="text-align:center;max-width:680px;margin-inline:auto;">
        <div class="eyebrow" style="justify-content:center;"><span class="bar"></span><span class="mono-label">{{ __('portal.pricing.eyebrow') }}</span></div>
        <h2 class="display"><span>{{ __('portal.pricing.title') }}</span> <span class="gold-text">{{ __('portal.pricing.title_gold') }}</span></h2>
        <p style="margin-inline:auto;">{{ __('portal.pricing.sub') }}</p>
      </div>
    @endif

    <div class="price-grid reveal">
      @foreach ($pricing as $card)
        <div class="plan{{ $card['featured'] ? ' hot' : '' }}">
          <div class="plan-top">
            <div class="tier">{{ $card['name'] }}</div>
            @if ($card['featured'])
              <span class="plan-badge">{{ __('portal.pricing.popular') }}</span>
            @endif
          </div>
          <div class="amt"><span class="big display gold-text" @if ($card['per']) dir="ltr" @endif>{{ $card['price'] }}</span>@if ($card['per'])<span class="per">{{ $card['per'] }}</span>@endif</div>
          <p class="pdesc">{{ $card['description'] }}</p>
          <p class="plabel">{{ $card['base'] ? __('portal.pricing.everything_in', ['name' => $card['base']]) : __('portal.pricing.includes') }}</p>
          <ul>
            @foreach ($card['lines'] as $line)
              <li>{!! $check !!}<span>{{ $line }}</span></li>
            @endforeach
          </ul>
          {{-- A contact-only package has no price to sign up against, so it
               goes to the contact form. Every other one starts with an
               account; a paid package is then asked for from the dashboard. --}}
          @if ($card['contact'])
            <a class="btn btn-line" data-magnetic href="{{ \App\Support\PortalUrl::to('contact') }}">{{ __('portal.pricing.cta_contact') }}</a>
          @else
            <a class="btn {{ $card['featured'] ? 'btn-gold' : 'btn-line' }}" data-magnetic href="{{ $ctaAuthedHref ?? route('register') }}">
              {{ $ctaAuthedLabel ?? ($card['free'] ? __('portal.pricing.cta_free') : __('portal.pricing.cta_choose', ['name' => $card['name']])) }}
            </a>
          @endif
        </div>
      @endforeach
    </div>
    <p class="price-note reveal"><b>{{ __('portal.pricing.note_bold') }}</b> <span>{{ __('portal.pricing.note') }}</span></p>
    @if ($fairUse)
      {{-- What every "Unlimited*" on the cards means in practice. --}}
      <p class="price-note fair-use reveal">{{ $fairUse }}</p>
    @endif
  </div>
</section>
