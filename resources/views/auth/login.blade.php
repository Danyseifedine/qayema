@php
    $locale    = app()->getLocale();
    $isRtl     = in_array($locale, config('locales.rtl', ['ar']));
    $dir       = $isRtl ? 'rtl' : 'ltr';
    $appName   = config('app.name', 'Qayema');

    $locales = config('locales.locales');

    $currentLocale = $locales[$locale] ?? $locales['en'];
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $dir }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ __('auth.login.eyebrow') }} — {{ $appName }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Geist:wght@300;400;500;600;700&family=Instrument+Serif:ital@0;1&family=Noto+Kufi+Arabic:wght@400;500;600&display=swap" rel="stylesheet">
@vite(['resources/css/login.css', 'resources/css/ui.css', 'resources/js/app.js'])
</head>

<body>
<div class="login" x-data="{ showPass: false }">

    {{-- ── Left: form column ─────────────────────────────── --}}
    <section class="form-col" dir="{{ $dir }}">

        <div class="form-top">
            <a class="brand" href="{{ url('/') }}">
                <img src="{{ asset('images/logo/logo.png') }}" alt="{{ $appName }}">
            </a>

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

            <form method="POST" action="{{ route('login') }}" class="fields">
                @csrf

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
                <a href="#">{{ __('auth.login.privacy') }}</a>
                <span class="dot-sep">·</span>
                <a href="#">{{ __('auth.login.terms') }}</a>
            </span>
        </div>
    </section>

    {{-- ── Right: brand panel ────────────────────────────── --}}
    <x-auth.brand-panel :is-rtl="$isRtl" />

</div>
</body>
</html>
