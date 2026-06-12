@php
    $appName    = config('app.name', 'Qayema');
    $locale     = app()->getLocale();
    $isRtl      = in_array($locale, config('locales.rtl', []));
    $dir        = $isRtl ? 'rtl' : 'ltr';
    $allLocales = config('locales.locales');
    $currencyOptions = collect(config('currencies', []))
        ->map(fn ($c, $code) => ['value' => $code, 'label' => $code, 'flag' => $c['symbol'] ?? '', 'meta' => $c['name'] ?? $code])
        ->values()->all();

    $currencySymbols = collect(config('currencies', []))->map(fn ($c) => $c['symbol'] ?? '$')->all();

    // Pre-fill existing restaurant data for back-navigation
    $existingCdTagIds   = $restaurant ? $restaurant->tags->whereIn('category', ['cuisine', 'dietary'])->pluck('id')->values()->all() : [];
    $existingCdTagSlugs = $restaurant ? $restaurant->tags->whereIn('category', ['cuisine', 'dietary'])->pluck('slug')->values()->all() : [];
    $existingVsTagIds   = $restaurant ? $restaurant->tags->whereIn('category', ['vibe', 'style'])->pluck('id')->values()->all() : [];
    $existingVsTagSlugs = $restaurant ? $restaurant->tags->whereIn('category', ['vibe', 'style'])->pluck('slug')->values()->all() : [];

    $allTemplatesJson = $templates->map(fn ($t) => [
        'id'        => $t->id,
        'name'      => $t->name,
        'thumbnail' => $t->getFirstMediaUrl('thumbnail'),
        'tagSlugs'  => $t->tags->pluck('slug')->values(),
    ]);

    $stepData = [
        ['key' => __('menu_owner.onboarding.step1_title'), 'short' => __('menu_owner.onboarding.step1_desc'), 'stage' => __('menu_owner.onboarding.step1_stage'), 'tag' => __('menu_owner.onboarding.step1_tag')],
        ['key' => __('menu_owner.onboarding.step2_title'), 'short' => __('menu_owner.onboarding.step2_desc'), 'stage' => __('menu_owner.onboarding.step2_stage'), 'tag' => __('menu_owner.onboarding.step2_tag')],
        ['key' => __('menu_owner.onboarding.step3_title'), 'short' => __('menu_owner.onboarding.step3_desc'), 'stage' => __('menu_owner.onboarding.step3_stage'), 'tag' => __('menu_owner.onboarding.step3_tag')],
        ['key' => __('menu_owner.onboarding.step4_title'), 'short' => __('menu_owner.onboarding.step4_desc'), 'stage' => __('menu_owner.onboarding.step4_stage'), 'tag' => __('menu_owner.onboarding.step4_tag')],
        ['key' => __('menu_owner.onboarding.step5_title'), 'short' => __('menu_owner.onboarding.step5_desc'), 'stage' => __('menu_owner.onboarding.step5_stage'), 'tag' => __('menu_owner.onboarding.step5_tag')],
        ['key' => __('menu_owner.onboarding.step6_title'), 'short' => __('menu_owner.onboarding.step6_desc'), 'stage' => __('menu_owner.onboarding.step6_stage'), 'tag' => __('menu_owner.onboarding.step6_tag')],
    ];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $appName }} — {{ __('menu_owner.onboarding.step1_title') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&family=Noto+Kufi+Arabic:wght@400;500;600&display=swap" rel="stylesheet">
@vite(['resources/css/login.css', 'resources/css/ui.css', 'resources/js/app.js'])
<script>
    window._onb = {
        step:       {{ $step }},
        totalSteps: {{ $totalSteps }},
        stepData:   @json($stepData),
        templates:  @json($allTemplatesJson),
        currencySymbols: @json($currencySymbols),
        currencyCodes: @json(array_keys(config('currencies', []))),
        locale:     @json($locale),
        routes: {
            advance:    @json(route('onboarding.advance')),
            checkSlug:  @json(route('onboarding.check-slug')),
        },
        appHost: @json(parse_url(config('app.url'), PHP_URL_HOST) ?? request()->getHost()),
        existing: {
            name:               @json($restaurant?->name ?? ''),
            slug:               @json($restaurant?->slug ?? ''),
            preferred_language: @json($restaurant?->preferred_language ?? $locale),
            country_code:       @json($restaurant?->country_code ?? ''),
            phone:              @json($restaurant?->phone ?? ''),
            currency:           @json($restaurant?->currency ?? 'USD'),
            cdTagIds:           @json($existingCdTagIds),
            cdTagSlugs:         @json($existingCdTagSlugs),
            vsTagIds:           @json($existingVsTagIds),
            vsTagSlugs:         @json($existingVsTagSlugs),
            templateId:         @json($restaurant?->template_id),
            hasLogo:            @json($restaurant?->hasMedia('logo') ?? false),
        },
        i18n: {
            nameRequired:     @json(__('menu_owner.onboarding.name_required')),
            nameMin:          @json(__('menu_owner.onboarding.name_min')),
            phoneRequired:    @json(__('menu_owner.onboarding.phone_required')),
            phoneInvalid:     @json(__('menu_owner.onboarding.phone_invalid')),
            currencyRequired: @json(__('menu_owner.onboarding.currency_required')),
            currencyInvalid:  @json(__('menu_owner.onboarding.currency_invalid')),
            logoRequired:     @json(__('menu_owner.onboarding.logo_required')),
            selectTemplate:   @json(__('menu_owner.onboarding.select_template')),
            uploadError:      @json(__('menu_owner.onboarding.upload_error')),
            somethingWrong:   @json(__('menu_owner.onboarding.something_wrong')),
            slugRequired:     @json(__('menu_owner.onboarding.slug_required')),
            slugTaken:        @json(__('menu_owner.onboarding.slug_taken')),
            slugChecking:     @json(__('menu_owner.onboarding.slug_checking')),
            tagsOne:          @json(__('menu_owner.onboarding.tags_count_one')),
            tagsMany:         @json(__('menu_owner.onboarding.tags_count')),
        },
    };
</script>
<style>
:root {
  --gold: #C8A85A;
  --gold-deep: #A8863C;
  --gold-soft: #D8C485;
  --muted-dark: rgba(246,241,232,.62);
  --line-dark: rgba(255,255,255,.10);
}
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; height: 100%; }
body { font-family: var(--font-sans); background: var(--paper); color: var(--ink); -webkit-font-smoothing: antialiased; min-height: 100vh; }
a { color: inherit; text-decoration: none; }
button { font: inherit; cursor: pointer; }
input, textarea, select { font: inherit; color: inherit; }

/* ── Shell ───────────────────────────────────────────── */
.shell { min-height: 100vh; display: grid; grid-template-columns: 1.05fr 1fr; }

/* ── Left: form pane ─────────────────────────────────── */
.left {
  position: relative; padding: 28px 56px 32px;
  display: flex; flex-direction: column;
  background:
    radial-gradient(80% 60% at -10% -10%, rgba(200,168,90,.30), transparent 60%),
    var(--paper);
}
.left-top { display: flex; align-items: center; justify-content: space-between; }
.brand { display: flex; align-items: center; gap: 10px; font-weight: 500; letter-spacing: -0.01em; font-size: 17px; }
.brand img { height: 70px; width: auto; }
.left-top-right { display: flex; align-items: center; gap: 18px; font-size: 12.5px; color: var(--muted); }
.save-pill { display: inline-flex; align-items: center; gap: 8px; padding: 5px 12px; border-radius: 999px; background: rgba(200,168,90,.10); color: var(--gold-deep); font-size: 11.5px; letter-spacing: .04em; }
.save-pill .dot { width: 6px; height: 6px; border-radius: 50%; background: var(--gold); box-shadow: 0 0 0 4px rgba(200,168,90,.15); }
.left-top-right a { color: var(--muted); }
.left-top-right a:hover { color: var(--ink); }

/* ── Step metadata ───────────────────────────────────── */
.step-meta { display: flex; align-items: baseline; gap: 14px; margin: 48px 0 8px; }
.step-meta .num { font-family: var(--font-display); font-style: italic; font-size: 88px; line-height: 0.85; color: var(--gold-deep); letter-spacing: -0.04em; font-weight: 400; }
.step-meta .num .of { font-size: 26px; color: var(--muted); margin-left: 2px; letter-spacing: 0; }
.step-meta .crumb { font-size: 11.5px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); flex: 1; padding-bottom: 6px; display: flex; flex-direction: column; gap: 6px; }
.step-meta .crumb .label { color: var(--ink); font-weight: 500; font-size: 13px; letter-spacing: 0; text-transform: none; }
.step-meta .crumb .dash { width: 28px; height: 1px; background: var(--ink); }

/* ── Form body ───────────────────────────────────────── */
.form-body { flex: 1; max-width: 540px; display: flex; flex-direction: column; }
h1.title { margin: 0; font-size: clamp(36px, 4.2vw, 58px); line-height: 1.0; letter-spacing: -0.03em; font-weight: 400; }
h1.title .it { font-family: var(--font-display); font-style: italic; color: var(--gold-deep); letter-spacing: -0.012em; }
.lead { margin: 16px 0 32px; font-size: 15px; line-height: 1.55; color: var(--muted); max-width: 44ch; }

/* ── Form fields ─────────────────────────────────────── */
.fields { display: flex; flex-direction: column; gap: 18px; }
.row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.field { display: flex; flex-direction: column; gap: 8px; }
.field-label { font-size: 11.5px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
.field-label .req { color: var(--gold-deep); margin-left: 4px; }
.f-input, .f-select {
  appearance: none; background: #fff; border: .5px solid var(--line); border-radius: 12px;
  padding: 14px 16px; font-size: 15px; letter-spacing: -0.005em; color: var(--ink); width: 100%;
  transition: border-color .2s ease, box-shadow .2s ease;
}
.f-input::placeholder { color: rgba(15,15,16,.32); }
.f-input:focus, .f-select:focus { outline: none; border-color: var(--gold); box-shadow: 0 0 0 3px rgba(200,168,90,.12); }
.f-select {
  background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%230F0F10' stroke-width='1.6'><path d='M6 9l6 6 6-6'/></svg>");
  background-repeat: no-repeat; background-position: right 14px center; padding-right: 40px;
}
.f-error { font-size: 12px; color: #dc2626; margin-top: 4px; }

/* ── Upload areas ────────────────────────────────────── */
.upload-area {
  border: 1.5px dashed rgba(15,15,16,.18); border-radius: 14px; cursor: pointer;
  overflow: hidden; transition: border-color .2s;
}
.upload-area:hover { border-color: var(--gold); }
.upload-inner { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px 16px; }
.upload-icon { color: var(--muted); }
.upload-lbl { font-size: 13px; font-weight: 600; color: var(--ink); }
.upload-hint { font-size: 11.5px; color: var(--muted); }
.upload-preview-img { width: 100%; height: 130px; object-fit: cover; display: block; }
.upload-logo-preview { width: 72px; height: 72px; object-fit: cover; border-radius: 10px; }
.upload-replace { font-size: 11.5px; color: var(--gold-deep); margin-top: 4px; }
.uploading-text { font-size: 12px; color: var(--muted); animation: onb-pulse 1s ease infinite; }
@keyframes onb-pulse { 0%,100%{opacity:1} 50%{opacity:.45} }

/* ── Tag chips ───────────────────────────────────────── */
.tag-section { margin-bottom: 20px; }
.tag-cat { font-size: 10.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); margin-bottom: 9px; }
.tag-chips { display: flex; flex-wrap: wrap; gap: 7px; }
.tag-chip {
  border: .5px solid var(--line); background: #fff; border-radius: 999px;
  padding: 6px 14px; font-size: 13px; color: var(--ink); cursor: pointer;
  transition: border-color .15s, background .15s;
}
.tag-chip:hover { border-color: rgba(200,168,90,.4); background: var(--paper); }
.tag-chip.on { border-color: var(--gold); background: rgba(200,168,90,.07); color: var(--gold-deep); font-weight: 500; }

/* ── Template grid ───────────────────────────────────── */
.tpl-section-lbl { font-size: 10.5px; font-weight: 700; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
.tpl-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 10px; margin-bottom: 18px; }
.tpl-card { border: .5px solid var(--line); background: #fff; border-radius: 14px; overflow: hidden; cursor: pointer; transition: border-color .15s, box-shadow .15s; }
.tpl-card:hover { border-color: rgba(200,168,90,.4); }
.tpl-card.on { border-color: var(--gold); box-shadow: 0 0 0 1px var(--gold); }
.tpl-thumb { width: 100%; height: 86px; background: var(--paper); display: flex; align-items: center; justify-content: center; font-size: 26px; }
.tpl-thumb img { width: 100%; height: 100%; object-fit: cover; display: block; }
.tpl-name { font-size: 12.5px; font-weight: 500; color: var(--ink); padding: 8px 10px 9px; }

/* ── Footer actions ──────────────────────────────────── */
.left-foot { margin-top: 28px; padding-top: 20px; border-top: .5px solid var(--line); display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.left-foot .meter { display: flex; align-items: center; gap: 12px; font-size: 12px; color: var(--muted); }
.meter-track { width: 180px; height: 3px; border-radius: 999px; background: rgba(15,15,16,.08); overflow: hidden; }
.meter-fill { height: 100%; background: var(--gold); transition: width .35s cubic-bezier(.5,.0,.2,1); }
.btn { display: inline-flex; align-items: center; gap: 8px; height: 46px; padding: 0 22px; border-radius: 999px; font-size: 14px; font-weight: 500; letter-spacing: -0.005em; border: .5px solid transparent; background: transparent; transition: transform .15s, background .2s, color .2s, opacity .2s; }
.btn:active { transform: translateY(1px); }
.btn:disabled { opacity: .35; cursor: not-allowed; }
.btn-ink { background: var(--ink); color: var(--paper); }
.btn-ink:hover:not(:disabled) { background: #1c1c1f; }
.btn-ink .arr { transition: transform .25s; }
.btn-ink:hover:not(:disabled) .arr { transform: translateX(3px); }
.btn-ghost { color: var(--ink); }
.btn-ghost:hover:not(:disabled) { background: rgba(15,15,16,.05); }
.btn .arr-left { transform: scaleX(-1); }
.btn:hover:not(:disabled) .arr-left { transform: scaleX(-1) translateX(3px); }
@keyframes btn-spin { to { transform: rotate(360deg); } }
.spin { animation: btn-spin .8s linear infinite; }

/* ── Right: dark editorial pane ──────────────────────── */
.right {
  position: relative; background: var(--ink); color: var(--paper);
  padding: 36px 48px 40px; display: flex; flex-direction: column; gap: 24px; overflow: hidden;
}
.right::before {
  content: ""; position: absolute; inset: 0; pointer-events: none;
  background:
    radial-gradient(70% 50% at 80% 10%, rgba(216,196,133,.18), transparent 60%),
    radial-gradient(60% 50% at 10% 100%, rgba(200,168,90,.10), transparent 70%);
}
/* Dots rail */
.right-rail { position: relative; z-index: 1; display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.rail-eyebrow { font-size: 11.5px; letter-spacing: .14em; text-transform: uppercase; color: var(--gold-soft); display: flex; align-items: center; gap: 10px; }
.rail-eyebrow .dash { width: 24px; height: 1px; background: var(--gold-soft); }
.dots { display: flex; align-items: center; gap: 8px; }
.dot-btn {
  appearance: none; border: 0; padding: 0;
  width: 32px; height: 32px; border-radius: 50%; background: transparent; color: var(--muted-dark);
  border: .5px solid var(--line-dark); display: grid; place-items: center;
  font-size: 12px; transition: all .2s; cursor: pointer;
}
.dot-btn:hover { color: var(--paper); border-color: rgba(255,255,255,.3); }
.dot-btn.done { background: var(--gold); color: var(--paper); border-color: var(--gold); }
.dot-btn.active { background: var(--paper); color: var(--ink); border-color: var(--paper); box-shadow: 0 0 0 4px rgba(246,241,232,.10); }
.dot-btn svg { width: 12px; height: 12px; }
.dot-line { width: 18px; height: 1px; background: var(--line-dark); }
.dot-line.done { background: var(--gold); }
/* Stage */
.stage { position: relative; z-index: 1; flex: 1; display: flex; flex-direction: column; gap: 20px; min-height: 0; }
.stage-head { display: flex; align-items: baseline; justify-content: space-between; gap: 14px; }
.stage-title { font-family: var(--font-display); font-style: italic; font-size: 36px; line-height: 1.05; letter-spacing: -0.012em; color: var(--paper); margin: 0; max-width: 16ch; }
.stage-tag { font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted-dark); display: inline-flex; align-items: center; gap: 8px; padding: 6px 12px; border: .5px solid var(--line-dark); border-radius: 999px; white-space: nowrap; }
.stage-tag .pip { width: 6px; height: 6px; border-radius: 50%; background: var(--gold-soft); box-shadow: 0 0 0 4px rgba(216,196,133,.15); }
/* Phone mockup */
.phone-wrap { flex: 1; display: grid; place-items: center; position: relative; min-height: 340px; }
.phone { position: relative; width: 268px; aspect-ratio: 9/19.2; background: #0a0a0b; border-radius: 40px; padding: 7px; box-shadow: 0 0 0 1.5px #2a2a2d, 0 50px 100px -30px rgba(0,0,0,.50); transform: rotate(-2deg); }
.phone-screen { width: 100%; height: 100%; background: var(--paper); border-radius: 32px; overflow: hidden; position: relative; color: var(--ink); display: flex; flex-direction: column; }
.phone-notch { position: absolute; top: 7px; left: 50%; transform: translateX(-50%); width: 28%; height: 16px; background: #0a0a0b; border-radius: 999px; z-index: 5; }
.ps-status { display: flex; justify-content: space-between; align-items: center; padding: 12px 22px 6px; font-size: 10px; font-weight: 600; }
.ps-header { padding: 14px 16px 10px; border-bottom: .5px solid var(--line); }
.ps-rest { font-family: var(--font-display); font-style: italic; font-size: 20px; line-height: 1.05; letter-spacing: -0.01em; }
.ps-sub { margin-top: 4px; font-size: 9px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted); }
.ps-list { flex: 1; padding: 6px 16px; overflow: hidden; }
.ps-item { display: grid; grid-template-columns: 1fr 30px; gap: 10px; padding: 9px 0; border-bottom: .5px solid var(--line); align-items: center; }
.ps-item:last-child { border-bottom: 0; }
.ps-item .name { font-size: 11px; font-weight: 500; }
.ps-item .desc { font-size: 8.5px; color: var(--muted); margin-top: 1px; }
.ps-item .price { font-size: 10px; font-weight: 500; text-align: right; }
.annot { position: absolute; z-index: 4; display: inline-flex; align-items: center; gap: 8px; padding: 7px 12px; background: rgba(255,255,255,.04); border: .5px solid var(--line-dark); backdrop-filter: blur(6px); border-radius: 999px; font-size: 11.5px; color: var(--paper); box-shadow: 0 14px 30px -16px rgba(0,0,0,.4); white-space: nowrap; }
.annot .pulse { width: 6px; height: 6px; border-radius: 50%; background: var(--gold-soft); box-shadow: 0 0 0 4px rgba(216,196,133,.18); }
.annot.tl { top: 10%; left: -4%; transform: rotate(-2deg); }
.annot.br { bottom: 16%; right: -4%; transform: rotate(2deg); }
/* Stat strip */
.stage-foot { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(3, 1fr); gap: 0; border-top: .5px solid var(--line-dark); padding-top: 18px; }
.stage-foot > div { border-right: .5px solid var(--line-dark); padding-right: 14px; }
.stage-foot > div:last-child { border-right: 0; }
.stage-foot .k { font-size: 10px; letter-spacing: .14em; text-transform: uppercase; color: var(--muted-dark); margin-bottom: 8px; }
.stage-foot .v { font-size: 20px; letter-spacing: -0.02em; color: var(--paper); line-height: 1; }
.stage-foot .v .it { font-family: var(--font-display); font-style: italic; color: var(--gold-soft); margin-right: 2px; }
.stage-foot .v .u { font-family: var(--font-display); font-style: italic; color: var(--muted-dark); font-size: .58em; margin-left: 3px; }
/* Alert */
.onb-alert { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 10px 14px; font-size: 13px; color: #dc2626; margin-bottom: 16px; }
/* Slug field — status border */
.slug-state-ok  .ui-input-wrap { border-color: #16a34a; box-shadow: 0 0 0 3px rgba(22,163,74,.10); }
.slug-state-bad .ui-input-wrap { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220,38,38,.10); }
.slug-icon-ok  { color: #16a34a; display:inline-flex; }
.slug-icon-bad { color: #dc2626; display:inline-flex; }
.slug-icon-chk { color: var(--muted); display:inline-flex; animation: onb-pulse 1s ease infinite; }
/* Responsive */
@media (max-width: 1024px) {
  .left { padding: 24px 36px; }
  .right { padding: 28px 32px 32px; }
  .step-meta .num { font-size: 68px; }
  .annot { display: none; }
  .right-rail { flex-direction: column; align-items: flex-start; gap: 12px; }
}
@media (max-width: 880px) {
  .shell { grid-template-columns: 1fr; }
  .right { display: none; }
  .step-meta { margin: 32px 0 8px; }
  .row-2 { grid-template-columns: 1fr; }
}
@media (max-width: 520px) {
  .left { padding: 18px 22px; }
  .step-meta .num { font-size: 54px; }
  h1.title { font-size: 32px; }
  .left-foot { flex-direction: column; align-items: stretch; gap: 14px; }
  .save-pill { display: none; }
  .meter-track { width: 100%; flex: 1; }
  .left-foot .meter { width: 100%; }
}
</style>
</head>
<body>
<div class="shell" x-data="wizard">

    {{-- ══════════════ LEFT: Form ══════════════ --}}
    <section class="left">

        {{-- Top bar --}}
        <header class="left-top">
            <div class="brand">
                <a href="{{ url('/') }}">
                    <img src="{{ asset('images/logo/q-logo.png') }}" alt="{{ $appName }}">
                </a>
            </div>
            <div class="left-top-right">
                {{-- Language switcher — available on every step --}}
                <div class="lang-picker" x-data="{ open: false }" @click.outside="open = false">
                    <button class="lang-trigger" @click="open = !open" :aria-expanded="open" type="button">
                        <span>{{ $allLocales[$locale]['flag'] ?? '' }}</span>
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
                        @foreach($allLocales as $code => $info)
                            <a class="lang-option {{ $locale === $code ? 'active' : '' }}"
                               href="{{ route('locale.switch', $code) }}">
                                <span>{{ $info['flag'] }}</span>
                                <span>{{ $info['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
                <a href="{{ route('logout') }}"
                   onclick="event.preventDefault(); document.getElementById('onb-logout').submit();">
                    {{ __('menu_owner.nav.log_out') }}
                </a>
                <form id="onb-logout" method="POST" action="{{ route('logout') }}" style="display:none">@csrf</form>
            </div>
        </header>

        {{-- Step number + crumb --}}
        <div class="step-meta">
            <div class="num">
                <span x-text="String(step).padStart(2, '0')"></span>
                <span class="of"> / <span x-text="String(totalSteps).padStart(2, '0')"></span></span>
            </div>
            <div class="crumb">
                <span class="dash"></span>
                <span x-text="cur.key"></span>
                <span class="label" x-text="cur.short"></span>
            </div>
        </div>

        {{-- Form body --}}
        <div class="form-body">

            <div class="onb-alert" x-show="globalError" x-text="globalError" x-cloak></div>

            {{-- ── Step 1 — Restaurant name + language ── --}}
            <div x-show="step === 1" x-cloak>
                <h1 class="title">{!! __('menu_owner.onboarding.step1_heading', ['em' => '<span class="it">'.__('menu_owner.onboarding.step1_em').'</span>']) !!}</h1>
                <p class="lead">{{ __('menu_owner.onboarding.step1_desc') }}</p>
                <div class="fields">
                    <x-ui.field name="name" :label="__('menu_owner.onboarding.name_label')" required>
                        <x-ui.input name="name" x-model="s1.name"
                            :placeholder="__('menu_owner.onboarding.name_placeholder')"
                            @input="onNameInput()"
                            autofocus required />
                        <div class="ui-help error" x-show="errors.name" x-text="errors.name" x-cloak></div>
                        <p class="ui-help" x-show="!errors.name" x-cloak>{{ __('menu_owner.onboarding.name_hint') }}</p>
                    </x-ui.field>

                    {{-- Menu link (slug) --}}
                    <x-ui.field name="slug" :label="__('menu_owner.onboarding.slug_label')" required>
                        <div :class="{
                            'slug-state-ok':  slugStatus === 'available',
                            'slug-state-bad': slugStatus === 'taken'
                        }">
                            <x-ui.input name="slug"
                                x-model="s1.slug"
                                @input="onSlugInput()"
                                @blur="onSlugBlur()"
                                :prefix="parse_url(config('app.url'), PHP_URL_HOST) . '/'"
                                placeholder="your-restaurant"
                                autocomplete="off"
                                spellcheck="false">
                                <span class="trail" style="cursor:default;gap:0">
                                    <span x-show="slugStatus === 'checking'" class="slug-icon-chk">
                                        <svg class="spin" width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="5.5" stroke="currentColor" stroke-width="1.5" stroke-dasharray="24" stroke-dashoffset="8"/></svg>
                                    </span>
                                    <span x-show="slugStatus === 'available'" class="slug-icon-ok">
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M2.5 7l3.5 3.5 5.5-6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </span>
                                    <span x-show="slugStatus === 'taken'" class="slug-icon-bad">
                                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M3.5 3.5l7 7M10.5 3.5l-7 7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                                    </span>
                                </span>
                            </x-ui.input>
                        </div>
                        <div class="ui-help error" x-show="errors.slug" x-text="errors.slug" x-cloak></div>
                        <div x-show="!errors.slug" x-cloak>
                            <p class="ui-help" x-show="slugStatus === 'idle' || slugStatus === 'checking'">{{ __('menu_owner.onboarding.slug_hint') }}</p>
                            <p class="ui-help" style="color:#16a34a" x-show="slugStatus === 'available'">{{ __('menu_owner.onboarding.slug_available') }}</p>
                            <p class="ui-help" style="color:#dc2626" x-show="slugStatus === 'taken'">{{ __('menu_owner.onboarding.slug_taken_hint') }}</p>
                        </div>
                    </x-ui.field>

                </div>
            </div>

            {{-- ── Step 2 — Contact + Currency ── --}}
            <div x-show="step === 2" x-cloak
                 @change="onContactChange($event)"
                 @combo-change="onCurrencyChange($event)"
                 @input="errors.phone = ''">
                <h1 class="title">{!! __('menu_owner.onboarding.step2_heading', ['em' => '<span class="it">'.__('menu_owner.onboarding.step2_em').'</span>']) !!}</h1>
                <p class="lead">{{ __('menu_owner.onboarding.step2_desc') }}</p>
                <div class="fields">
                    <x-ui.field :label="__('menu_owner.onboarding.phone_label')" required>
                        <x-ui.phone name="phone" cc-name="country_code"
                            :value="$restaurant?->phone"
                            :cc-value="$restaurant?->country_code ?? 'LB'" />
                        <div class="ui-help error" x-show="errors.phone" x-text="errors.phone" x-cloak></div>
                        <p class="ui-help" x-show="!errors.phone" x-cloak>{{ __('menu_owner.onboarding.phone_hint') }}</p>
                    </x-ui.field>

                    <x-ui.field name="currency" :label="__('menu_owner.onboarding.currency_label')" required>
                        <x-ui.combo name="currency"
                            :options="$currencyOptions"
                            :value="$restaurant?->currency ?? 'USD'"
                            :placeholder="__('menu_owner.restaurant.currency_placeholder')"
                            :up="true" />
                        <div class="ui-help error" x-show="errors.currency" x-text="errors.currency" x-cloak></div>
                        <p class="ui-help" x-show="!errors.currency" x-cloak>{{ __('menu_owner.onboarding.currency_hint') }}</p>
                    </x-ui.field>
                </div>
            </div>

            {{-- ── Step 3 — Branding ── --}}
            <div x-show="step === 3" x-cloak>
                <h1 class="title">{!! __('menu_owner.onboarding.step3_heading', ['em' => '<span class="it">'.__('menu_owner.onboarding.step3_em').'</span>']) !!}</h1>
                <p class="lead">{{ __('menu_owner.onboarding.step3_desc') }}</p>
                <div class="fields">
                    <x-ui.field :label="__('menu_owner.onboarding.logo_label')"
                                :optional="__('menu_owner.onboarding.optional')">
                        <x-ui.dropzone name="logo" context="logo"
                            :value="$restaurant?->getFirstMediaUrl('logo') ?: null"
                            :hint="__('menu_owner.onboarding.logo_hint')" />
                        <div class="ui-help error" x-show="errors.logo" x-text="errors.logo" x-cloak></div>
                        <p class="ui-help" x-show="!errors.logo" x-cloak>{{ __('menu_owner.onboarding.logo_field_hint') }}</p>
                    </x-ui.field>

                    <x-ui.field :label="__('menu_owner.onboarding.cover_label')"
                                :optional="__('menu_owner.onboarding.optional')">
                        <x-ui.dropzone name="cover_image" context="cover_image"
                            :value="$restaurant?->getFirstMediaUrl('cover_image') ?: null"
                            :hint="__('menu_owner.onboarding.cover_hint')" />
                        <p class="ui-help">{{ __('menu_owner.onboarding.cover_field_hint') }}</p>
                    </x-ui.field>
                </div>
            </div>

            {{-- ── Step 4 — Cuisine + Dietary tags ── --}}
            <div x-show="step === 4" x-cloak>
                <h1 class="title">{!! __('menu_owner.onboarding.step4_heading', ['em' => '<span class="it">'.__('menu_owner.onboarding.step4_em').'</span>']) !!}</h1>
                <p class="lead">{{ __('menu_owner.onboarding.step4_desc') }}</p>
                <p class="ui-help" style="margin-bottom:18px">{{ __('menu_owner.onboarding.tags_hint') }}</p>
                @foreach(['cuisine', 'dietary'] as $cat)
                @if(isset($tags[$cat]) && $tags[$cat]->isNotEmpty())
                <div class="tag-section">
                    <div class="tag-cat">{{ __('menu_owner.onboarding.tag_'.$cat) }}</div>
                    <div class="tag-chips">
                        @foreach($tags[$cat] as $tag)
                        <button type="button" class="tag-chip"
                                :class="{ on: cdTagIds.includes({{ $tag->id }}) }"
                                @click="toggleCd({{ $tag->id }}, '{{ $tag->slug }}')">{{ $tag->name }}</button>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach
            </div>

            {{-- ── Step 5 — Vibe + Style tags ── --}}
            <div x-show="step === 5" x-cloak>
                <h1 class="title">{!! __('menu_owner.onboarding.step5_heading', ['em' => '<span class="it">'.__('menu_owner.onboarding.step5_em').'</span>']) !!}</h1>
                <p class="lead">{{ __('menu_owner.onboarding.step5_desc') }}</p>
                <p class="ui-help" style="margin-bottom:18px">{{ __('menu_owner.onboarding.vibe_hint') }}</p>
                @foreach(['vibe', 'style'] as $cat)
                @if(isset($tags[$cat]) && $tags[$cat]->isNotEmpty())
                <div class="tag-section">
                    <div class="tag-cat">{{ __('menu_owner.onboarding.tag_'.$cat) }}</div>
                    <div class="tag-chips">
                        @foreach($tags[$cat] as $tag)
                        <button type="button" class="tag-chip"
                                :class="{ on: vsTagIds.includes({{ $tag->id }}) }"
                                @click="toggleVs({{ $tag->id }}, '{{ $tag->slug }}')">{{ $tag->name }}</button>
                        @endforeach
                    </div>
                </div>
                @endif
                @endforeach
            </div>

            {{-- ── Step 6 — Template picker ── --}}
            <div x-show="step === 6" x-cloak>
                <h1 class="title">{!! __('menu_owner.onboarding.step6_heading', ['em' => '<span class="it">'.__('menu_owner.onboarding.step6_em').'</span>']) !!}</h1>
                <p class="lead">{{ __('menu_owner.onboarding.step6_desc') }}</p>
                <p class="ui-help" style="margin-bottom:18px">{{ __('menu_owner.onboarding.template_hint') }}</p>
                <div class="f-error" x-show="errors.template" x-text="errors.template" x-cloak style="margin-bottom:12px"></div>

                <template x-if="recommendedTemplates.length > 0">
                    <div>
                        <div class="tpl-section-lbl">{{ __('menu_owner.onboarding.recommended') }}</div>
                        <div class="tpl-grid">
                            <template x-for="t in recommendedTemplates" :key="t.id">
                                <div class="tpl-card" :class="{ on: selectedTemplateId === t.id }" @click="selectedTemplateId = t.id">
                                    <div class="tpl-thumb">
                                        <template x-if="t.thumbnail"><img :src="t.thumbnail" :alt="t.name"></template>
                                        <template x-if="!t.thumbnail"><span>🎨</span></template>
                                    </div>
                                    <div class="tpl-name" x-text="t.name"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="otherTemplates.length > 0">
                    <div>
                        <div class="tpl-section-lbl">{{ __('menu_owner.onboarding.all_templates') }}</div>
                        <div class="tpl-grid">
                            <template x-for="t in otherTemplates" :key="t.id">
                                <div class="tpl-card" :class="{ on: selectedTemplateId === t.id }" @click="selectedTemplateId = t.id">
                                    <div class="tpl-thumb">
                                        <template x-if="t.thumbnail"><img :src="t.thumbnail" :alt="t.name"></template>
                                        <template x-if="!t.thumbnail"><span>🎨</span></template>
                                    </div>
                                    <div class="tpl-name" x-text="t.name"></div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <template x-if="allTemplates.length === 0">
                    <p style="color:var(--muted);font-size:13.5px;padding:24px 0;text-align:center">{{ __('menu_owner.onboarding.no_templates') }}</p>
                </template>
            </div>

        </div>{{-- /form-body --}}

        {{-- Footer --}}
        <footer class="left-foot">
            <div class="meter">
                <div class="meter-track">
                    <div class="meter-fill" :style="`width: ${progress}%`"></div>
                </div>
                <span x-text="'{{ __('menu_owner.onboarding.pct_complete') }}'.replace(':pct', progress)"></span>
            </div>
            <div style="display:flex;gap:8px">
                <button type="button" class="btn btn-ghost" x-show="step > 1" @click="back()" x-cloak>
                    <svg class="arr-left" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    {{ __('menu_owner.onboarding.back') }}
                </button>
                <button type="button" class="btn btn-ink" @click="advance()"
                        :disabled="loading">
                    <template x-if="loading">
                        <svg class="spin" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                    </template>
                    <template x-if="!loading">
                        <span x-text="step === totalSteps ? '{{ __('menu_owner.onboarding.finish') }}' : '{{ __('menu_owner.onboarding.continue') }}'"></span>
                    </template>
                    <template x-if="loading">
                        <span>{{ __('menu_owner.onboarding.please_wait') }}</span>
                    </template>
                    <template x-if="!loading">
                        <svg class="arr" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </template>
                </button>
            </div>
        </footer>

    </section>

    {{-- ══════════════ RIGHT: Dark editorial ══════════════ --}}
    <aside class="right">

        {{-- Dots rail --}}
        <div class="right-rail">
            <div class="rail-eyebrow">
                <span class="dash"></span>
                {{ __('menu_owner.onboarding.onboarding_label') }}
            </div>
            <div class="dots">
                @for($i = 1; $i <= $totalSteps; $i++)
                    <button type="button" class="dot-btn"
                            :class="{ done: step > {{ $i }}, active: step === {{ $i }} }"
                            @click="if (step > {{ $i }}) step = {{ $i }}"
                            title="{{ $stepData[$i - 1]['key'] }}">
                        <template x-if="step > {{ $i }}">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
                        </template>
                        <template x-if="step <= {{ $i }}">
                            <span>{{ $i }}</span>
                        </template>
                    </button>
                    @if($i < $totalSteps)
                    <span class="dot-line" :class="{ done: step > {{ $i }} }"></span>
                    @endif
                @endfor
            </div>
        </div>

        {{-- Stage --}}
        <div class="stage">
            <div class="stage-head">
                <h2 class="stage-title" x-text="cur.stage"></h2>
                <span class="stage-tag">
                    <span class="pip"></span>
                    <span x-text="`{{ __('menu_owner.onboarding.step_label') }} ${String(step).padStart(2,'0')}`"></span>
                </span>
            </div>

            {{-- Phone mockup --}}
            <div class="phone-wrap">
                <span class="annot tl">
                    <span class="pulse"></span>
                    <span x-text="step === 1 ? s1.name || '{{ $appName }}' : step === 2 ? '{{ __('menu_owner.onboarding.step2_title') }}' : step === 3 ? '{{ __('menu_owner.onboarding.logo_label') }}' : step === 4 ? tagsLabel(cdTagIds.length) : step === 5 ? tagsLabel(vsTagIds.length) : '{{ __('menu_owner.onboarding.stat_stage') }}'"></span>
                </span>

                <div class="phone">
                    <div class="phone-screen">
                        <div class="phone-notch"></div>
                        <div class="ps-status">
                            <span>9:41</span>
                            <span style="opacity:.7">● ● ●</span>
                        </div>
                        <div class="ps-header">
                            <div class="ps-rest" x-text="s1.name || '{{ $appName }}'"></div>
                            <div class="ps-sub" x-text="selectedCurrency + ' · ' + (s1.preferred_language || '{{ $locale }}').toUpperCase()"></div>
                        </div>
                        <div class="ps-list">
                            <div class="ps-item">
                                <div><div class="name">Saffron risotto</div><div class="desc">Aged carnaroli, lemon</div></div>
                                <div class="price" x-text="currencySymbol + '24'"></div>
                            </div>
                            <div class="ps-item">
                                <div><div class="name">Heirloom tomato</div><div class="desc">Burrata, basil oil</div></div>
                                <div class="price" x-text="currencySymbol + '18'"></div>
                            </div>
                            <div class="ps-item">
                                <div><div class="name">Za'atar flatbread</div><div class="desc">Stone-baked, labneh</div></div>
                                <div class="price" x-text="currencySymbol + '12'"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <span class="annot br">
                    <span class="pulse"></span>
                    <span x-text="step === 2 ? selectedCurrency : step === 6 && selectedTemplateId ? '{{ __('menu_owner.onboarding.template_selected') }}' : '{{ __('menu_owner.onboarding.pct_complete') }}'.replace(':pct', progress)"></span>
                </span>
            </div>

            {{-- Stat strip --}}
            <div class="stage-foot">
                <div style="padding: 5px;">
                    <div class="k">{{ __('menu_owner.onboarding.stat_setup') }}</div>
                    <div class="v"><span class="it" x-text="progress"></span><span class="u">%</span></div>
                </div>
                <div style="padding: 5px;">
                    <div class="k">{{ __('menu_owner.onboarding.stat_time_left') }}</div>
                    <div class="v"><span class="it" x-text="totalSteps - step + 1"></span><span class="u">min</span></div>
                </div>
                <div style="padding: 5px;">
                    <div class="k">{{ __('menu_owner.onboarding.stat_stage') }}</div>
                    <div class="v" style="font-family:var(--font-display);font-style:italic;color:var(--gold-soft);font-size:16px" x-text="cur.tag"></div>
                </div>
            </div>
        </div>

    </aside>

</div>
<script>
document.addEventListener('alpine:init', () => {
    const _o = window._onb;

    const dom = (name) => document.querySelector(`[name="${name}"]`)?.value ?? '';

    Alpine.data('wizard', () => ({
        step:       _o.step,
        totalSteps: _o.totalSteps,
        loading:    false,
        errors:     {},
        globalError: '',

        stepData: _o.stepData,
        get cur()      { return this.stepData[this.step - 1]; },
        get progress() { return Math.round(((this.step - 1) / (this.totalSteps - 1)) * 100); },

        /* Step 1 */
        s1: { name: _o.existing.name, preferred_language: _o.existing.preferred_language, slug: _o.existing.slug },
        slugEdited:  !!_o.existing.slug, // true when user has manually touched the slug field
        slugStatus:  'idle',             // idle | checking | available | taken
        _slugTimer:  null,

        get slugPreview() { return _o.appHost + '/' + (this.s1.slug || '…'); },

        onNameInput() {
            this.errors.name = '';
            if (!this.slugEdited) {
                this.s1.slug = this.s1.name
                    .toLowerCase()
                    .trim()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/[\s_]+/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
                this.checkSlugAvailability();
            }
        },

        onSlugInput() {
            this.errors.slug = '';
            this.slugEdited = true;
            this.s1.slug = this.s1.slug
                .toLowerCase()
                .replace(/\s+/g, '-')        // spaces -> hyphens FIRST
                .replace(/[^a-z0-9-]/g, '')  // then strip anything else
                .replace(/-+/g, '-')
                .replace(/^-+/, '');
            this.checkSlugAvailability();
        },

        onSlugBlur() {
            this.s1.slug = this.s1.slug.replace(/-+$/, '');
            this.checkSlugAvailability();
        },

        checkSlugAvailability() {
            clearTimeout(this._slugTimer);
            const slug = this.s1.slug;
            if (slug.length < 2) { this.slugStatus = 'idle'; return; }
            this.slugStatus = 'checking';
            this._slugTimer = setTimeout(async () => {
                try {
                    const res = await fetch(`${_o.routes.checkSlug}?slug=${encodeURIComponent(slug)}`);
                    const data = await res.json();
                    if (this.s1.slug === slug) {
                        this.slugStatus = data.available ? 'available' : 'taken';
                    }
                } catch { this.slugStatus = 'idle'; }
            }, 380);
        },

        /* Step 2 — currency tracked for right-panel display only */
        selectedCurrency: _o.existing.currency || 'USD',
        get currencySymbol() {
            return (_o.currencySymbols && _o.currencySymbols[this.selectedCurrency]) || '$';
        },
        onContactChange(e) {
            if (e.target && e.target.name === 'currency') { this.selectedCurrency = e.target.value || 'USD'; this.errors.currency = ''; }
            if (e.target && (e.target.name === 'phone' || e.target.name === 'country_code')) { this.errors.phone = ''; }
        },
        onCurrencyChange(e) {
            this.selectedCurrency = (e.detail && e.detail.value) || 'USD';
            this.errors.currency = '';
        },
        tagsLabel(n) {
            return n + ' ' + (n === 1 ? _o.i18n.tagsOne : _o.i18n.tagsMany);
        },

        /* Step 4 */
        cdTagIds: _o.existing.cdTagIds, cdTagSlugs: _o.existing.cdTagSlugs,
        toggleCd(id, slug) {
            const i = this.cdTagIds.indexOf(id);
            if (i === -1) { this.cdTagIds.push(id); this.cdTagSlugs.push(slug); }
            else { this.cdTagIds.splice(i, 1); this.cdTagSlugs.splice(this.cdTagSlugs.indexOf(slug), 1); }
        },

        /* Step 5 */
        vsTagIds: _o.existing.vsTagIds, vsTagSlugs: _o.existing.vsTagSlugs,
        toggleVs(id, slug) {
            const i = this.vsTagIds.indexOf(id);
            if (i === -1) { this.vsTagIds.push(id); this.vsTagSlugs.push(slug); }
            else { this.vsTagIds.splice(i, 1); this.vsTagSlugs.splice(this.vsTagSlugs.indexOf(slug), 1); }
        },

        /* Step 6 */
        selectedTemplateId: _o.existing.templateId,
        allTemplates: _o.templates,
        get allTagSlugs() { return [...this.cdTagSlugs, ...this.vsTagSlugs]; },
        get recommendedTemplates() {
            if (!this.allTagSlugs.length) return [];
            return this.allTemplates.filter(t => t.tagSlugs.some(s => this.allTagSlugs.includes(s)));
        },
        get otherTemplates() { return this.allTemplates; },


        /* ── Snapshot system ─────────────────────────────────────────
           Captures each step's state when the step becomes active.
           advance() skips the API call if nothing changed.           */
        _snap: {},

        _captureSnap(step) {
            switch (step) {
                case 1:
                    this._snap[1] = JSON.stringify({ name: this.s1.name.trim(), lang: this.s1.preferred_language, slug: this.s1.slug });
                    break;
                case 2:
                    this._snap[2] = JSON.stringify({ cc: dom('country_code'), phone: dom('phone'), currency: dom('currency') });
                    break;
                case 3:
                    this._snap[3] = `${dom('logo_key')}|${dom('cover_image_key')}`;
                    break;
                case 4:
                    this._snap[4] = [...this.cdTagIds].sort().join(',');
                    break;
                case 5:
                    this._snap[5] = [...this.vsTagIds].sort().join(',');
                    break;
                case 6:
                    this._snap[6] = String(this.selectedTemplateId ?? '');
                    break;
            }
        },

        _isUnchanged() {
            switch (this.step) {
                case 1: return this._snap[1] === JSON.stringify({ name: this.s1.name.trim(), lang: this.s1.preferred_language, slug: this.s1.slug });
                case 2: return this._snap[2] === JSON.stringify({ cc: dom('country_code'), phone: dom('phone'), currency: dom('currency') });
                case 3: return this._snap[3] === `${dom('logo_key')}|${dom('cover_image_key')}`;
                case 4: return this._snap[4] === [...this.cdTagIds].sort().join(',');
                case 5: return this._snap[5] === [...this.vsTagIds].sort().join(',');
                case 6: return this._snap[6] === String(this.selectedTemplateId ?? '');
                default: return false;
            }
        },

        init() {
            // Capture snapshot when step changes (use $nextTick so DOM components have rendered)
            this.$watch('step', step => this.$nextTick(() => this._captureSnap(step)));
            this.$nextTick(() => this._captureSnap(this.step));

            // ── GSAP step transitions ──────────────────────────────
            let prevStep = this.step;

            this.$watch('step', (newStep) => {
                const dir = newStep > prevStep ? 1 : -1;
                prevStep = newStep;
                this.$nextTick(() => {
                    if (!window.gsap) return;
                    // Slide the form content in from the correct direction
                    gsap.fromTo('.form-body',
                        { opacity: 0, x: dir * 30 },
                        { opacity: 1, x: 0, duration: 0.35, ease: 'power2.out' }
                    );
                    // Counter pops up
                    gsap.fromTo('.step-meta .num',
                        { opacity: 0, y: -10 },
                        { opacity: 1, y: 0, duration: 0.3, ease: 'back.out(2)', delay: 0.06 }
                    );
                    // Crumb slides in
                    gsap.fromTo('.step-meta .crumb',
                        { opacity: 0, x: dir * 14 },
                        { opacity: 1, x: 0, duration: 0.28, ease: 'power2.out', delay: 0.09 }
                    );
                    // Right-panel stage heading
                    gsap.fromTo('.stage-head',
                        { opacity: 0, y: 10 },
                        { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out', delay: 0.13 }
                    );
                });
            });

            // ── Initial page entrance ──────────────────────────────
            if (window.gsap) {
                gsap.from('.left',  { opacity: 0, x: -24, duration: 0.5, ease: 'power2.out', delay: 0.05 });
                gsap.from('.right', { opacity: 0, x:  24, duration: 0.5, ease: 'power2.out', delay: 0.15 });
            }
        },

        /* Advance */
        async advance() {
            if (this.loading) return;
            this.errors = {}; this.globalError = '';

            // ── Frontend validation (always runs) ──
            if (this.step === 1) {
                if (!this.s1.name.trim()) { this.errors.name = _o.i18n.nameRequired; return; }
                if (this.s1.name.trim().length < 2) { this.errors.name = _o.i18n.nameMin; return; }
                if (!this.s1.slug || this.s1.slug.length < 2) { this.errors.slug = _o.i18n.slugRequired; return; }
                if (this.slugStatus === 'taken') { this.errors.slug = _o.i18n.slugTaken; return; }
                if (this.slugStatus === 'checking') { this.errors.slug = _o.i18n.slugChecking; return; }
            }
            if (this.step === 2) {
                const phone = dom('phone').trim();
                if (!phone) { this.errors.phone = _o.i18n.phoneRequired; return; }
                if (!/^(?=(?:\D*\d){6,})[0-9+()\s.\-]{6,30}$/.test(phone)) { this.errors.phone = _o.i18n.phoneInvalid; return; }
                const currency = dom('currency');
                if (!currency) { this.errors.currency = _o.i18n.currencyRequired; return; }
                if (!(_o.currencyCodes || []).includes(currency)) { this.errors.currency = _o.i18n.currencyInvalid; return; }
            }
            // Step 3 (branding) — logo is optional; nothing to block on.
            if (this.step === 6 && !this.selectedTemplateId) { this.errors.template = _o.i18n.selectTemplate; return; }

            // ── Skip API if nothing changed (never skip the final step — it must complete server-side) ──
            if (this._isUnchanged() && this.step < this.totalSteps) {
                this.step = Math.min(this.step + 1, this.totalSteps);
                return;
            }

            this.loading = true;
            try {
                let body = { _step: this.step };
                if (this.step === 1) {
                    body = { ...body, ...this.s1 };
                } else if (this.step === 2) {
                    body = { ...body, country_code: dom('country_code'), phone: dom('phone'), currency: dom('currency') };
                } else if (this.step === 3) {
                    body = { ...body, logo_key: dom('logo_key'), cover_image_key: dom('cover_image_key') };
                } else if (this.step === 4) {
                    body = { ...body, tag_ids: this.cdTagIds };
                } else if (this.step === 5) {
                    body = { ...body, tag_ids: this.vsTagIds };
                } else if (this.step === 6) {
                    body = { ...body, template_id: this.selectedTemplateId };
                }

                const res = await fetch(_o.routes.advance, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(body),
                });

                if (res.status === 422) {
                    const d = await res.json();
                    this.errors = d.errors ?? {};
                    this.globalError = d.message ?? '';
                    return;
                }

                const d = await res.json();
                if (d.completed) { window.location = d.redirect; return; }
                this.step = Math.min(d.step, this.totalSteps);

                // Update snapshot after a successful save so the next
                // back-and-continue on this step is also skippable.
                this._captureSnap(this.step - 1);
            } catch { this.globalError = _o.i18n.somethingWrong; }
            finally { this.loading = false; }
        },

        back() { if (this.step > 1) this.step = Math.max(1, this.step - 1); },
    }));
});
</script>
</body>
</html>
