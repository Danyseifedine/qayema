@extends('portal.auth.layout')

@php $seoTitle = __('auth.passwords.reset_title'); @endphp

@section('eyebrow', __('auth.passwords.eyebrow'))
@section('title', __('auth.passwords.reset_title'))
@section('subtitle', __('auth.passwords.reset_subtitle'))

@section('form')
    <form method="POST" action="{{ route('password.store') }}" class="fields" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div class="ui-field">
            <label class="ui-label" for="email">
                <span>{{ __('auth.login.email') }}</span>
            </label>
            <x-ui.input id="email" name="email" type="email"
                icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/></svg>'
                :value="old('email', $email)"
                required autocomplete="email" />
            @error('email')
                <div class="ui-help error">{{ $message }}</div>
            @enderror
        </div>

        <div class="ui-field">
            <label class="ui-label" for="password">
                <span>{{ __('auth.passwords.new_password') }}</span>
            </label>
            <x-ui.input id="password" name="password" type="password" :reveal="true"
                icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>'
                placeholder="••••••••••••"
                required autofocus autocomplete="new-password" />
            @error('password')
                <div class="ui-help error">{{ $message }}</div>
            @enderror
        </div>

        <div class="ui-field">
            <label class="ui-label" for="password_confirmation">
                <span>{{ __('auth.passwords.confirm_password') }}</span>
            </label>
            <x-ui.input id="password_confirmation" name="password_confirmation" type="password" :reveal="true"
                icon='<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>'
                placeholder="••••••••••••"
                required autocomplete="new-password" />
        </div>

        <button type="submit" class="submit">
            <span>{{ __('auth.passwords.reset_submit') }}</span>
            <svg class="arr" width="14" height="14" viewBox="0 0 24 24" fill="none"
                 stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M5 12h14M13 6l6 6-6 6"/>
            </svg>
        </button>
    </form>
@endsection
