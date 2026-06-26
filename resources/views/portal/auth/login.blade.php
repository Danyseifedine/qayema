@extends('portal.layout.master.master')

@php
    $bare = true;
    $locale    = app()->getLocale();
    $isRtl     = in_array($locale, config('locales.rtl', ['ar']));
    $dir       = $isRtl ? 'rtl' : 'ltr';
    $appName   = config('app.name', 'Qayema');
    $locales   = config('locales.locales');
    $currentLocale = $locales[$locale] ?? $locales['en'];
    $seoTitle  = __('auth.login.eyebrow').' — '.$appName;
@endphp

@push('styles')
<link rel="stylesheet" href="{{ asset('portal/css/components/ui.css') }}?v={{ @filemtime(public_path('portal/css/components/ui.css')) ?: '1' }}">
<link rel="stylesheet" href="{{ asset('portal/css/pages/login.css') }}?v={{ @filemtime(public_path('portal/css/pages/login.css')) ?: '1' }}">
@endpush

@section('content')
<div class="login" x-data="{ showPass: false }">

    {{-- ── Left: form column ─────────────────────────────── --}}
    <section class="form-col" dir="{{ $dir }}">

        <div class="form-top">
            <a class="brand" href="{{ url('/') }}">
                <img src="{{ asset('images/logo/logo.png') }}" alt="{{ $appName }}">
            </a>

            <div class="top-actions">
            <button class="theme-tog" type="button" aria-label="theme">
                <span class="knob">
                    <svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                    <svg class="moon" viewBox="0 0 24 24" fill="currentColor"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
                </span>
            </button>

            {{-- Server-side language picker --}}
            <div class="lang-picker" x-data="{ open: false }" @click.outside="open = false">
                <button class="lang-trigger" @click="open = !open" :aria-expanded="open" type="button">
                    <span>{{ $currentLocale['flag'] }}</span>
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
                    @foreach($locales as $code => $info)
                        <a class="lang-option {{ $locale === $code ? 'active' : '' }}"
                           href="{{ route('locale.switch', $code) }}">
                            <span>{{ $info['flag'] }}</span>
                            <span>{{ $info['name'] }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
            </div>
        </div>

        <div class="form-body">

            <div class="eyebrow">
                <span class="dash"></span>
                <span>{{ __('auth.login.eyebrow') }}</span>
                <span class="dot"></span>
            </div>

            <h1 class="title">{!! __('auth.login.title', ['name' => '<span class="it">'.$appName.'</span>']) !!}</h1>

            <p class="subtitle">{{ __('auth.login.subtitle') }}</p>

            @if (session('status'))
                <div class="alert alert-status">{{ session('status') }}</div>
            @endif

            @if (session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            {{-- Google OAuth --}}
            <a href="{{ route('auth.google') }}" class="google-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                </svg>
                {{ __('auth.login.google_btn') }}
            </a>

            <div class="login-divider"><span>{{ __('auth.login.divider') }}</span></div>

            <form method="POST" action="{{ route('login') }}" class="fields" novalidate
                  x-data="{ emailError: '', passwordError: '' }"
                  @input="emailError = ''; passwordError = ''"
                  @submit="
                      emailError = ''; passwordError = '';
                      const em = $event.target.querySelector('input[name=email]');
                      const pw = $event.target.querySelector('input[name=password]');
                      if (em && ! em.checkValidity()) { emailError = em.validationMessage; }
                      if (pw && ! pw.checkValidity()) { passwordError = pw.validationMessage; }
                      if (emailError || passwordError) { $event.preventDefault(); (emailError ? em : pw).focus(); }
                  ">
                @csrf

                @if(config('services.recaptcha.enabled'))
                    <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
                @endif

                {{-- Email --}}
                <div class="ui-field">
                    <label class="ui-label" for="email">
                        <span>{{ __('auth.login.email') }}</span>
                    </label>
                    <x-ui.input id="email" name="email" type="email"
                        icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>'
                        placeholder="{{ __('auth.login.email_placeholder') }}"
                        :value="old('email')"
                        required autofocus autocomplete="email" />
                    @error('email')
                        <div class="ui-help error">{{ $message }}</div>
                    @enderror
                    <div class="ui-help error" x-show="emailError" x-text="emailError" x-cloak></div>
                </div>

                {{-- Password --}}
                <div class="ui-field">
                    <label class="ui-label" for="password">
                        <span>{{ __('auth.login.password') }}</span>
                    </label>
                    <x-ui.input id="password" name="password" type="password" :reveal="true"
                        icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>'
                        placeholder="••••••••••••"
                        autocomplete="current-password" required />
                    @error('password')
                        <div class="ui-help error">{{ $message }}</div>
                    @enderror
                    <div class="ui-help error" x-show="passwordError" x-text="passwordError" x-cloak></div>
                </div>

                {{-- Remember me --}}
                <div class="row">
                    <x-ui.checkbox name="remember" :checked="true" :olive="true">
                        <span>{{ __('auth.login.remember') }}</span>
                    </x-ui.checkbox>
                </div>

                {{-- Submit --}}
                <button type="submit" class="submit">
                    <span>{{ __('auth.login.submit') }}</span>
                    <svg class="arr" width="14" height="14" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14M13 6l6 6-6 6"/>
                    </svg>
                </button>

            </form>
        </div>

        <div class="form-foot">
            <span>© {{ date('Y') }} {{ $appName }}</span>
            <span>
                <a href="{{ route('privacy') }}">{{ __('auth.login.privacy') }}</a>
                <span class="dot-sep">·</span>
                <a href="{{ route('terms') }}">{{ __('auth.login.terms') }}</a>
            </span>
        </div>
    </section>

    {{-- ── Right: cover image ───────────────────────────── --}}
    <aside class="brand-col">
        <img class="brand-image" src="{{ asset('portal/images/login.jpg') }}" alt="" onerror="this.style.display='none'">
    </aside>

</div>
@endsection

@push('scripts')
@if(config('services.recaptcha.enabled'))
    <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
    <script>
        (function () {
            const siteKey = @json(config('services.recaptcha.site_key'));

            function refreshCaptchaToken() {
                grecaptcha.ready(function () {
                    grecaptcha.execute(siteKey, { action: 'login' }).then(function (token) {
                        const field = document.getElementById('g-recaptcha-response');
                        if (field) {
                            field.value = token;
                        }
                    });
                });
            }

            refreshCaptchaToken();
            setInterval(refreshCaptchaToken, 110000);
        })();
    </script>
@endif
@endpush
