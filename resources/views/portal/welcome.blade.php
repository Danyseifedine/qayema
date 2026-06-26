@extends('portal.layout.master.master')

@php
    // Inline icon set (server-rendered; was previously injected by landing.js)
    $ICON = [
        'camera' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8a2 2 0 0 1 2-2h2l1.5-2h7L17 6h2a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><circle cx="12" cy="12.5" r="3.5"/></svg>',
        'globe' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/></svg>',
        'palette' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a9 9 0 1 0 0 18c1.7 0 2-1.3 1.2-2.2-.8-1 .1-2.3 1.3-2.3H17a4 4 0 0 0 4-4 8.5 8.5 0 0 0-9-9.5z"/><circle cx="7.5" cy="11" r="1.2" fill="currentColor"/><circle cx="11" cy="7.5" r="1.2" fill="currentColor"/><circle cx="15.5" cy="9" r="1.2" fill="currentColor"/></svg>',
        'qr' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><path d="M14 14h3v3M21 14v.01M21 21v-4M17 21h1M14 21h.01"/></svg>',
        'whatsapp' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21l1.7-5A8 8 0 1 1 8 19.3z"/><path d="M8.5 9.5c0 3 2 5 5 5 .8 0 1.3-.7.8-1.3l-1-1c-.3-.3-.7-.2-1 0l-.4.3c-.8-.4-1.4-1-1.8-1.8l.3-.4c.2-.3.3-.7 0-1l-1-1c-.6-.5-1.3 0-1.3.8z"/></svg>',
        'chart' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4v16h16"/><path d="M8 14l3-4 3 2 4-6"/></svg>',
        'layers' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l9 5-9 5-9-5z"/><path d="M3 13l9 5 9-5M3 17l9 5 9-5" opacity=".6"/></svg>',
        'phone' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="2.5" width="10" height="19" rx="3"/><path d="M11 18.5h2"/></svg>',
        'user' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.5-6 8-6s8 2 8 6"/></svg>',
        'check' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>',
        'spark' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M12 3l1.8 5.4L19 10l-5.2 1.6L12 17l-1.8-5.4L5 10l5.2-1.6z"/></svg>',
    ];
@endphp

@section('content')

  {{-- ===== HERO ===== --}}
  <header class="hero">
    <span class="hero-blob b1"></span>
    <span class="hero-blob b2"></span>
    <span class="hero-blob b3"></span>

    <div class="wrap hero-center">
      <h1 class="display">{!! __('portal.hero.title') !!}</h1>
      <p class="hero-sub">{{ __('portal.hero.sub') }}</p>

      <form class="hero-form" action="{{ route('register') }}" method="GET">
        <input type="email" placeholder="{{ __('portal.hero.email_placeholder') }}" />
        <button type="submit">{{ __('portal.hero.cta') }}</button>
      </form>

      <div class="hero-rating">
        <span class="hero-stars">
          @for ($i = 0; $i < 5; $i++)
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3 6.3 6.9.9-5 4.8 1.2 6.8L12 17.8 5.9 20.8 7.1 14l-5-4.8 6.9-.9z"/></svg>
          @endfor
        </span>
        <span class="sep"></span>
        <span>{!! __('portal.hero.rating') !!}</span>
      </div>
    </div>

    <div class="hero-shot">
      <div class="browser">
        <div class="bw-bar">
          <span class="dot r"></span><span class="dot y"></span><span class="dot g"></span>
          <span class="url">app.qayema.io/menu</span>
        </div>
        <div class="bw-body">
          <iframe src="{{ asset('landing/qayema-dashboard.html') }}" title="Qayema dashboard" loading="lazy" scrolling="no" tabindex="-1" aria-hidden="true"></iframe>
        </div>
      </div>
    </div>
  </header>

  {{-- ===== PROBLEM ===== --}}
  <section class="sec" id="problem">
    <div class="wrap">
      <div class="sec-head reveal">
        <div class="eyebrow"><span class="bar"></span><span class="mono-label">{{ __('portal.problem.eyebrow') }}</span></div>
        <h2 class="display"><span>{{ __('portal.problem.title') }}</span> <span class="gold-text">{{ __('portal.problem.title_gold') }}</span></h2>
      </div>
      <div class="prob-grid" data-stagger>
        @foreach (__('portal.problem.cards') as $i => $card)
          <div class="prob-card">
            <div class="num display">0{{ $i + 1 }}</div>
            <h3>{{ $card['title'] }}</h3>
            <p>{{ $card['desc'] }}</p>
            <div class="strike">
              @if ($i === 0)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="6" y="3" width="12" height="18" rx="1"/><path d="M9 8h6M9 12h6M9 16h3"/><line x1="4" y1="21" x2="20" y2="3" stroke-width="2"/></svg>
              @elseif ($i === 1)
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-3a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-2M6 14h12v7H6z"/><line x1="4" y1="21" x2="20" y2="3" stroke-width="2"/></svg>
              @else
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/><line x1="3" y1="21" x2="21" y2="3" stroke-width="2"/></svg>
              @endif
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ===== FEATURES ===== --}}
  <section class="sec" id="features" style="padding-top:0;">
    <div class="wrap">
      <div class="sec-head reveal">
        <div class="eyebrow"><span class="bar"></span><span class="mono-label">{{ __('portal.features.eyebrow') }}</span></div>
        <h2 class="display"><span>{{ __('portal.features.title') }}</span> <span class="gold-text">{{ __('portal.features.title_gold') }}</span></h2>
        <p>{{ __('portal.features.sub') }}</p>
      </div>
      <div class="features-wrap" id="featuresBox">
        <div class="feat-lead reveal">
          <div class="lead-text">
            <span class="badge">{{ __('portal.features.lead.badge') }}</span>
            <h3 class="display">{{ __('portal.features.lead.title') }}</h3>
            <p>{{ __('portal.features.lead.desc') }}</p>
          </div>
          <div class="lead-vis">
            <div class="scan-demo">
              <div class="paper"><div class="pl"></div><div class="pl s"></div><div class="pl"></div><div class="pl s"></div></div>
              <span class="arrow">{!! $ICON['spark'] !!}</span>
              <div class="digi"><div class="dl"><span>Mezze</span><span class="p">$8</span></div><div class="dl"><span>Tagine</span><span class="p">$22</span></div><div class="dl"><span>Baklava</span><span class="p">$9</span></div></div>
              <div class="scan-beam"></div>
            </div>
          </div>
        </div>
        <div class="feat-grid" data-stagger>
          @foreach (__('portal.features.cells') as $cell)
            <div class="feat-cell">
              <div class="c-ic">{!! $ICON[$cell['icon']] !!}</div>
              <h3>{{ $cell['title'] }}</h3>
              <p>{{ $cell['desc'] }}</p>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </section>

  {{-- ===== HOW IT WORKS (pinned horizontal) ===== --}}
  <section class="how" id="how">
    <div class="pin-wrap">
      <div class="how-track" id="howTrack">
        <div class="how-intro">
          <span class="mono-label">{{ __('portal.how.eyebrow') }}</span>
          <h2 class="display"><span>{{ __('portal.how.title') }}</span> <span class="gold-text">{{ __('portal.how.title_gold') }}</span></h2>
          <p>{{ __('portal.how.sub') }}</p>
        </div>
        @foreach (__('portal.how.steps') as $step)
          <div class="how-step">
            <div class="how-card">
              <div>
                <div class="idx display">{{ $step['idx'] }}</div>
                <h3 class="display">{{ $step['title'] }}</h3>
                <p>{{ $step['desc'] }}</p>
              </div>
              <div class="panel"><div class="big-ic">{!! $ICON[$step['icon']] !!}</div></div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
    <div class="how-progress"><i></i></div>
  </section>

  {{-- ===== STATS ===== --}}
  <section class="sec" id="stats" style="padding:90px 0;">
    <div class="wrap">
      <div class="stats reveal">
        @foreach (__('portal.stats') as $stat)
          <div class="stat">
            <div class="v"><span class="gold-text" data-count="{{ $stat['value'] }}" data-suf="{{ $stat['suffix'] }}" @if (str_contains((string) $stat['value'], '.')) data-dec="1" @endif>0{{ $stat['suffix'] }}</span></div>
            <div class="k">{{ $stat['label'] }}</div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ===== PRICING ===== --}}
  <section class="sec" id="pricing" style="padding-top:0;">
    <div class="wrap">
      <div class="sec-head reveal" style="text-align:center;max-width:680px;margin-inline:auto;">
        <div class="eyebrow" style="justify-content:center;"><span class="bar"></span><span class="mono-label">{{ __('portal.pricing.eyebrow') }}</span></div>
        <h2 class="display"><span>{{ __('portal.pricing.title') }}</span> <span class="gold-text">{{ __('portal.pricing.title_gold') }}</span></h2>
        <p style="margin-inline:auto;">{{ __('portal.pricing.sub') }}</p>
      </div>

      <div class="price-grid reveal">
        <div class="plan">
          <div class="tier">{{ __('portal.pricing.free.tier') }}</div>
          <div class="amt"><span class="big display gold-text">{{ __('portal.pricing.free.price') }}</span><span class="per">{{ __('portal.pricing.free.per') }}</span></div>
          <p class="pdesc">{{ __('portal.pricing.free.desc') }}</p>
          <ul>
            @foreach (__('portal.pricing.free.features') as $feature)
              <li>{!! $ICON['check'] !!}<span>{{ $feature }}</span></li>
            @endforeach
          </ul>
          <a class="btn btn-line" data-magnetic href="{{ route('register') }}">{{ __('portal.pricing.free.cta') }}</a>
        </div>

        <div class="plan hot">
          <div class="tier">{{ __('portal.pricing.premium.tier') }}</div>
          <div class="amt"><span class="big display gold-text">{{ __('portal.pricing.premium.price') }}</span><span class="per">{{ __('portal.pricing.premium.per') }}</span></div>
          <p class="pdesc">{{ __('portal.pricing.premium.desc') }}</p>
          <ul>
            @foreach (__('portal.pricing.premium.features') as $feature)
              <li>{!! $ICON['check'] !!}<span>{{ $feature }}</span></li>
            @endforeach
          </ul>
          <a class="btn btn-gold" data-magnetic href="{{ route('register') }}">{{ __('portal.pricing.premium.cta') }}</a>
        </div>
      </div>
      <p class="price-note reveal"><b>{{ __('portal.pricing.note_bold') }}</b> <span>{{ __('portal.pricing.note') }}</span></p>
    </div>
  </section>

  {{-- ===== TESTIMONIALS ===== --}}
  <section class="sec" id="stories" style="padding-top:0;">
    <div class="wrap">
      <div class="sec-head reveal">
        <div class="eyebrow"><span class="bar"></span><span class="mono-label">{{ __('portal.stories.eyebrow') }}</span></div>
        <h2 class="display"><span>{{ __('portal.stories.title') }}</span> <span class="gold-text">{{ __('portal.stories.title_gold') }}</span></h2>
      </div>
      <div class="quotes" data-stagger>
        @foreach (__('portal.stories.quotes') as $i => $quote)
          <div class="quote {{ $i === 0 ? 'lead' : '' }}">
            <div><div class="mk">"</div><p class="qt">{{ $quote['quote'] }}</p></div>
            <div class="by"><span class="av display">{{ $quote['avatar'] }}</span><div><div class="nm">{{ $quote['name'] }}</div><div class="rl">{{ $quote['role'] }}</div></div></div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  {{-- ===== FAQ ===== --}}
  <section class="sec" id="faq" style="padding-top:0;">
    <div class="wrap">
      <div class="sec-head reveal" style="text-align:center;max-width:640px;margin-inline:auto;">
        <div class="eyebrow" style="justify-content:center;"><span class="bar"></span><span class="mono-label">{{ __('portal.faq.eyebrow') }}</span></div>
        <h2 class="display"><span>{{ __('portal.faq.title') }}</span> <span class="gold-text">{{ __('portal.faq.title_gold') }}</span></h2>
      </div>
      <div class="faq" id="faqList">
        @foreach (__('portal.faq.items') as $item)
          <div class="faq-item">
            <button class="faq-q" type="button"><span>{{ $item['q'] }}</span><span class="pm"></span></button>
            <div class="faq-a"><div class="inner">{{ $item['a'] }}</div></div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

@endsection
