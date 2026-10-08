@extends('portal.auth.layout')

@php $seoTitle = __('auth.login.eyebrow'); @endphp

@section('eyebrow', __('auth.login.eyebrow'))
@section('title')
    {!! __('auth.login.title', ['name' => '<span class="it">'.config('app.name', 'Qayema').'</span>']) !!}
@endsection
@section('subtitle', __('auth.login.subtitle'))

@section('form')
    @include('portal.auth.partials.google-button')

    <div class="login-divider"><span>{{ __('auth.login.divider') }}</span></div>

    <form method="POST" action="{{ route('login') }}" class="fields" novalidate
          x-data="{ loginError: '', passwordError: '' }"
          @input="loginError = ''; passwordError = ''"
          @submit="
              loginError = ''; passwordError = '';
              const lg = $event.target.querySelector('input[name=login]');
              const pw = $event.target.querySelector('input[name=password]');
              if (lg && ! lg.checkValidity()) { loginError = lg.validationMessage; }
              if (pw && ! pw.checkValidity()) { passwordError = pw.validationMessage; }
              if (loginError || passwordError) { $event.preventDefault(); (loginError ? lg : pw).focus(); }
          ">
        @csrf

        @if(config('services.recaptcha.enabled'))
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
        @endif

        {{-- Email, or the username of an account made with one --}}
        <div class="ui-field">
            <label class="ui-label" for="login">
                <span>{{ __('auth.login.login') }}</span>
            </label>
            <x-ui.input id="login" name="login" type="text"
                icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>'
                placeholder="{{ __('auth.login.login_placeholder') }}"
                :value="old('login')"
                required autofocus autocomplete="username" autocapitalize="none" spellcheck="false" />
            @error('login')
                <div class="ui-help error">{{ $message }}</div>
            @enderror
            <div class="ui-help error" x-show="loginError" x-text="loginError" x-cloak></div>
        </div>

        {{-- Password --}}
        <div class="ui-field">
            <label class="ui-label" for="password">
                <span>{{ __('auth.login.password') }}</span>
                <a class="opt" href="{{ route('password.request') }}">{{ __('auth.passwords.forgot_link') }}</a>
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
            <x-ui.checkbox name="remember" :checked="true">
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

        <p class="ui-help" style="text-align:center;margin-top:16px">
            {{ __('auth.login.no_account') }}
            <a href="{{ route('signup') }}">{{ __('auth.login.create_account') }}</a>
        </p>
    </form>
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
