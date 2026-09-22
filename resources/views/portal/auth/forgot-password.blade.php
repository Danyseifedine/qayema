@extends('portal.auth.layout')

@php $seoTitle = __('auth.passwords.forgot_title'); @endphp

@section('eyebrow', __('auth.passwords.eyebrow'))
@section('title', __('auth.passwords.forgot_title'))
@section('subtitle', __('auth.passwords.forgot_subtitle'))

@section('form')
    <form method="POST" action="{{ route('password.email') }}" class="fields" novalidate>
        @csrf

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

        <button type="submit" class="submit">
            <span>{{ __('auth.passwords.send_link') }}</span>
            <svg class="arr" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>

        <p class="ui-help" style="text-align:center;margin-top:16px">
            <a href="{{ route('login') }}">{{ __('auth.passwords.back_to_login') }}</a>
        </p>
    </form>
@endsection
