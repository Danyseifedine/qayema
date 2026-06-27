@extends('portal.layout.master.master')

@php $bare = true; @endphp

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

    $stepData = [
        ['key' => __('menu_owner.onboarding.step1_title'), 'short' => __('menu_owner.onboarding.step1_desc'), 'stage' => __('menu_owner.onboarding.step1_stage'), 'tag' => __('menu_owner.onboarding.step1_tag')],
        ['key' => __('menu_owner.onboarding.step2_title'), 'short' => __('menu_owner.onboarding.step2_desc'), 'stage' => __('menu_owner.onboarding.step2_stage'), 'tag' => __('menu_owner.onboarding.step2_tag')],
        ['key' => __('menu_owner.onboarding.step3_title'), 'short' => __('menu_owner.onboarding.step3_desc'), 'stage' => __('menu_owner.onboarding.step3_stage'), 'tag' => __('menu_owner.onboarding.step3_tag')],
        ['key' => __('menu_owner.onboarding.step4_title'), 'short' => __('menu_owner.onboarding.step4_desc'), 'stage' => __('menu_owner.onboarding.step4_stage'), 'tag' => __('menu_owner.onboarding.step4_tag')],
        ['key' => __('menu_owner.onboarding.step5_title'), 'short' => __('menu_owner.onboarding.step5_desc'), 'stage' => __('menu_owner.onboarding.step5_stage'), 'tag' => __('menu_owner.onboarding.step5_tag')],
    ];
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('portal/css/components/ui.css') }}?v={{ @filemtime(public_path('portal/css/components/ui.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('portal/css/pages/login.css') }}?v={{ @filemtime(public_path('portal/css/pages/login.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('portal/css/pages/onboarding.css') }}?v={{ @filemtime(public_path('portal/css/pages/onboarding.css')) ?: '1' }}">
@endpush

@section('content')
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
                <button class="theme-tog" type="button" aria-label="theme">
                    <span class="knob">
                        <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                        <svg class="moon" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                    </span>
                </button>
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
                    <x-ui.field :label="__('menu_owner.onboarding.logo_label')" required>
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
                <div class="f-error" x-show="errors.cdTags" x-text="errors.cdTags" x-cloak style="margin-bottom:12px"></div>
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
                <div class="f-error" x-show="errors.vsTags" x-text="errors.vsTags" x-cloak style="margin-bottom:12px"></div>
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
                            <div class="ps-sub" x-text="selectedCurrency + ' · ' + (s1.default_locale || '{{ $locale }}').toUpperCase()"></div>
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
                    <span x-text="step === 2 ? selectedCurrency : '{{ __('menu_owner.onboarding.pct_complete') }}'.replace(':pct', progress)"></span>
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
@endsection

@push('scripts')
<script>
window._onb = {
        step:       {{ $step }},
        totalSteps: {{ $totalSteps }},
        stepData:   @json($stepData),
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
            default_locale: @json($restaurant?->default_locale ?? (in_array($locale, ['ar','en']) ? $locale : 'ar')),
            country_code:       @json($restaurant?->country_code ?? ''),
            phone:              @json($restaurant?->phone ?? ''),
            currency:           @json($restaurant?->currency ?? 'USD'),
            cdTagIds:           @json($existingCdTagIds),
            cdTagSlugs:         @json($existingCdTagSlugs),
            vsTagIds:           @json($existingVsTagIds),
            vsTagSlugs:         @json($existingVsTagSlugs),
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
            tagsRequired:     @json(__('menu_owner.onboarding.tags_required')),
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
        s1: { name: _o.existing.name, default_locale: _o.existing.default_locale, slug: _o.existing.slug },
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

        /* Step 3 — a logo already saved on the restaurant satisfies the requirement.
           The dropzone is a self-contained component, so the wizard tracks this
           page-load flag separately (a fresh upload is detected via logo_key). */
        hasLogo: _o.existing.hasLogo,

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

        /* ── Snapshot system ─────────────────────────────────────────
           Captures each step's state when the step becomes active.
           advance() skips the API call if nothing changed.           */
        _snap: {},

        _captureSnap(step) {
            switch (step) {
                case 1:
                    this._snap[1] = JSON.stringify({ name: this.s1.name.trim(), lang: this.s1.default_locale, slug: this.s1.slug });
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
            }
        },

        _isUnchanged() {
            switch (this.step) {
                case 1: return this._snap[1] === JSON.stringify({ name: this.s1.name.trim(), lang: this.s1.default_locale, slug: this.s1.slug });
                case 2: return this._snap[2] === JSON.stringify({ cc: dom('country_code'), phone: dom('phone'), currency: dom('currency') });
                case 3: return this._snap[3] === `${dom('logo_key')}|${dom('cover_image_key')}`;
                case 4: return this._snap[4] === [...this.cdTagIds].sort().join(',');
                case 5: return this._snap[5] === [...this.vsTagIds].sort().join(',');
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
            // Step 3 (branding) — a logo is required (an existing one counts).
            if (this.step === 3 && !this.hasLogo && !dom('logo_key')) { this.errors.logo = _o.i18n.logoRequired; return; }
            // Steps 4 & 5 — at least one tag must be selected on each.
            if (this.step === 4 && this.cdTagIds.length === 0) { this.errors.cdTags = _o.i18n.tagsRequired; return; }
            if (this.step === 5 && this.vsTagIds.length === 0) { this.errors.vsTags = _o.i18n.tagsRequired; return; }

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
@endpush
