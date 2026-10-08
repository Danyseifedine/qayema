@extends('portal.auth.layout')

@php $seoTitle = __('auth.signup.eyebrow'); @endphp

@section('eyebrow', __('auth.signup.eyebrow'))
@section('title', __('auth.signup.title'))
@section('subtitle', __('auth.signup.subtitle'))

@section('form')
    @include('portal.auth.partials.google-button')

    <div class="login-divider"><span>{{ __('auth.signup.divider') }}</span></div>

    <form method="POST" action="{{ route('signup.store') }}" class="fields" novalidate>
        @csrf

        <div class="ui-field">
            <label class="ui-label" for="name">
                <span>{{ __('auth.signup.name') }}</span>
            </label>
            <x-ui.input id="name" name="name" type="text"
                icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>'
                placeholder="{{ __('auth.signup.name_placeholder') }}"
                :value="old('name')"
                required autofocus autocomplete="name" maxlength="255" />
            @error('name')
                <div class="ui-help error">{{ $message }}</div>
            @enderror
        </div>

        <div class="ui-field">
            <label class="ui-label" for="username">
                <span>{{ __('auth.signup.username') }}</span>
            </label>
            <x-ui.input id="username" name="username" type="text"
                icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/></svg>'
                placeholder="{{ __('auth.signup.username_placeholder') }}"
                :value="old('username')"
                required autocomplete="username" autocapitalize="none" spellcheck="false" maxlength="30" dir="ltr" />
            @error('username')
                <div class="ui-help error">{{ $message }}</div>
            @else
                <div class="ui-help">{{ __('auth.signup.username_help') }}</div>
            @enderror
        </div>

        <div class="ui-field">
            <label class="ui-label" for="email">
                <span>{{ __('auth.signup.email') }}</span>
            </label>
            <x-ui.input id="email" name="email" type="email"
                icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>'
                placeholder="{{ __('auth.login.email_placeholder') }}"
                :value="old('email')"
                autocomplete="email" maxlength="255" />
            @error('email')
                <div class="ui-help error">{{ $message }}</div>
            @else
                <div class="ui-help">{{ __('auth.signup.email_help') }}</div>
            @enderror
        </div>

        <div class="ui-field">
            <label class="ui-label" for="password">
                <span>{{ __('auth.login.password') }}</span>
            </label>
            <x-ui.input id="password" name="password" type="password" :reveal="true"
                icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>'
                placeholder="••••••••••••"
                required autocomplete="new-password" />
            @error('password')
                <div class="ui-help error">{{ $message }}</div>
            @enderror
        </div>

        <div class="ui-field">
            <label class="ui-label" for="password_confirmation">
                <span>{{ __('auth.signup.confirm_password') }}</span>
            </label>
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password" :reveal="true"
                icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>'
                placeholder="••••••••••••"
                required autocomplete="new-password" />
        </div>

        <button type="submit" class="submit">
            <span>{{ __('auth.signup.submit') }}</span>
            <svg class="arr" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>

        <p class="ui-help" style="text-align:center;margin-top:16px">
            {{ __('auth.signup.have_account') }}
            <a href="{{ route('login') }}">{{ __('auth.signup.sign_in') }}</a>
        </p>
    </form>
@endsection
