<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Qayema') }}</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/css/ui.css', 'resources/css/dashboard.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">

@php
    $authUser   = auth()->user();
    $restaurant = $authUser?->restaurant;

    $locales = [
        'en' => ['name' => 'English',    'flag' => '🇬🇧'],
        'ar' => ['name' => 'العربية',    'flag' => '🇸🇦'],
        'fr' => ['name' => 'Français',   'flag' => '🇫🇷'],
        'de' => ['name' => 'Deutsch',    'flag' => '🇩🇪'],
        'es' => ['name' => 'Español',    'flag' => '🇪🇸'],
        'it' => ['name' => 'Italiano',   'flag' => '🇮🇹'],
        'pt' => ['name' => 'Português',  'flag' => '🇵🇹'],
        'ru' => ['name' => 'Русский',    'flag' => '🇷🇺'],
        'tr' => ['name' => 'Türkçe',     'flag' => '🇹🇷'],
        'hi' => ['name' => 'हिन्दी',     'flag' => '🇮🇳'],
        'zh' => ['name' => '中文',        'flag' => '🇨🇳'],
        'ja' => ['name' => '日本語',      'flag' => '🇯🇵'],
        'ko' => ['name' => '한국어',      'flag' => '🇰🇷'],
        'nl' => ['name' => 'Nederlands', 'flag' => '🇳🇱'],
    ];
    $currentLocale     = app()->getLocale();
    $currentLocaleData = $locales[$currentLocale] ?? $locales['en'];

    $userAvatar = $authUser?->socialAccounts()->where('provider', 'google')->value('avatar');
    $userInitial = mb_strtoupper(mb_substr($authUser?->name ?? '?', 0, 1));
@endphp

<div class="sbl-app" x-data="{ mobileNav: false }">

    {{-- ══════════ Sidebar ══════════ --}}
    <aside class="sbl-side" :class="{ 'open': mobileNav }">

        {{-- Brand --}}
        <a href="{{ route('dashboard') }}" class="sbl-brand">
            <img src="{{ asset('images/logo/q-logo.png') }}" alt="{{ config('app.name') }}" style="height:70px;width:auto;object-fit:contain">
        </a>

        {{-- Operate --}}
        <div class="sbl-nav-group">
            <div class="sbl-nav-label">Operate</div>
            <nav class="sbl-nav-list">

                <a href="{{ route('dashboard') }}"
                   class="sbl-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   @click="mobileNav = false">
                    <span class="sbl-nav-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
                            <polyline points="9 22 9 12 15 12 15 22"/>
                        </svg>
                    </span>
                    {{ __('menu_owner.nav.dashboard') }}
                </a>

                <a href="{{ route('menu-owner.dishes.index') }}"
                   class="sbl-nav-item {{ request()->routeIs('menu-owner.dishes.*') ? 'active' : '' }}"
                   @click="mobileNav = false">
                    <span class="sbl-nav-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 2v7c0 1.1.9 2 2 2h4a2 2 0 0 0 2-2V2"/>
                            <path d="M7 2v20"/>
                            <path d="M21 15V2a5 5 0 0 0-5 5v6c0 1.1.9 2 2 2h3zm0 0v7"/>
                        </svg>
                    </span>
                    {{ __('menu_owner.nav.dishes') }}
                    @if($restaurant)
                        @php $dishCount = $restaurant->dishes()->count(); @endphp
                        @if($dishCount > 0)
                            <span class="sbl-nav-badge">{{ $dishCount }}</span>
                        @endif
                    @endif
                </a>

                <a href="{{ route('menu-owner.categories.index') }}"
                   class="sbl-nav-item {{ request()->routeIs('menu-owner.categories.*') ? 'active' : '' }}"
                   @click="mobileNav = false">
                    <span class="sbl-nav-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                            <line x1="7" y1="7" x2="7.01" y2="7"/>
                        </svg>
                    </span>
                    {{ __('menu_owner.nav.categories') }}
                </a>

                <a href="{{ route('menu-owner.menu-scan.index') }}"
                   class="sbl-nav-item {{ request()->routeIs('menu-owner.menu-scan.*') ? 'active' : '' }}"
                   @click="mobileNav = false">
                    <span class="sbl-nav-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                    </span>
                    Scan Menu
                </a>

                <a href="{{ route('menu-owner.qr-code.index') }}"
                   class="sbl-nav-item {{ request()->routeIs('menu-owner.qr-code.*') ? 'active' : '' }}"
                   @click="mobileNav = false">
                    <span class="sbl-nav-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="6" height="6" rx="1"/>
                            <rect x="15" y="3" width="6" height="6" rx="1"/>
                            <rect x="3" y="15" width="6" height="6" rx="1"/>
                            <path d="M15 15h2v2h-2zM19 15v2M15 19h2M19 19v.5"/>
                        </svg>
                    </span>
                    {{ __('menu_owner.nav.qr_code') }}
                </a>

            </nav>
        </div>

        {{-- Grow --}}
        <div class="sbl-nav-group">
            <div class="sbl-nav-label">Grow</div>
            <nav class="sbl-nav-list">

                <a href="{{ route('menu-owner.statistics.index') }}"
                   class="sbl-nav-item {{ request()->routeIs('menu-owner.statistics.*') ? 'active' : '' }}"
                   @click="mobileNav = false">
                    <span class="sbl-nav-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"/>
                            <line x1="12" y1="20" x2="12" y2="4"/>
                            <line x1="6"  y1="20" x2="6"  y2="14"/>
                        </svg>
                    </span>
                    {{ __('menu_owner.nav.statistics') }}
                </a>

                <a href="{{ route('menu-owner.social-links.index') }}"
                   class="sbl-nav-item {{ request()->routeIs('menu-owner.social-links.*') ? 'active' : '' }}"
                   @click="mobileNav = false">
                    <span class="sbl-nav-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="18" cy="5" r="3"/>
                            <circle cx="6" cy="12" r="3"/>
                            <circle cx="18" cy="19" r="3"/>
                            <line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/>
                            <line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/>
                        </svg>
                    </span>
                    {{ __('menu_owner.nav.social_links') }}
                </a>

                <a href="{{ route('menu-owner.restaurant.index') }}"
                   class="sbl-nav-item {{ request()->routeIs('menu-owner.restaurant.*') ? 'active' : '' }}"
                   @click="mobileNav = false">
                    <span class="sbl-nav-ico">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9h18v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V9z"/>
                            <path d="m3 9 2.45-4.9A2 2 0 0 1 7.24 3h9.52a2 2 0 0 1 1.79 1.1L21 9"/>
                            <path d="M12 3v6"/>
                        </svg>
                    </span>
                    {{ __('menu_owner.nav.restaurant') }}
                </a>

            </nav>
        </div>

        {{-- Footer --}}
        <div class="sbl-foot">
            <a href="{{ route('profile.edit') }}" class="sbl-me">
                @if($userAvatar)
                    <img src="{{ $userAvatar }}" alt="{{ $authUser->name }}" class="sbl-me-av sbl-me-av-img">
                @else
                    <span class="sbl-me-av">{{ $userInitial }}</span>
                @endif
                <div class="sbl-me-meta">
                    <div class="sbl-me-name">{{ $authUser->name }}</div>
                    <div class="sbl-me-role">{{ $authUser->isAdmin() ? 'Admin' : 'Owner' }}</div>
                </div>
            </a>
        </div>

    </aside>

    {{-- Mobile overlay --}}
    <div class="sbl-overlay"
         x-show="mobileNav"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileNav = false"
         style="display:none"></div>

    {{-- ══════════ Main ══════════ --}}
    <div class="sbl-main">

        {{-- Impersonation banner --}}
        @if(is_impersonating())
            <div class="sbl-impersonate">
                <span>Viewing as <strong>{{ $authUser->name }}</strong></span>
                <a href="{{ route('impersonate.leave') }}">{{ __('menu_owner.nav.leave_impersonation') }}</a>
            </div>
        @endif

        {{-- Mobile topbar --}}
        <div class="sbl-mobile-bar">
            <button class="sbl-hamburger" @click="mobileNav = true" aria-label="Open navigation">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.6" stroke-linecap="round">
                    <path d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <span class="sbl-mb-name">{{ $restaurant?->name ?? config('app.name') }}</span>
            <span style="width:36px"></span>
        </div>

        {{-- ── Top bar (breadcrumbs + right actions) ── --}}
        <div class="sbl-topbar">
            <div class="sbl-crumbs">
                @if(isset($breadcrumbs))
                    {{ $breadcrumbs }}
                @else
                    <span class="sbl-crumb-here">{{ config('app.name') }}</span>
                @endif
            </div>
            <div class="sbl-topbar-right">

                {{-- Search --}}
                <div class="sbl-search">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>
                    </svg>
                    <input type="text" placeholder="Search…" readonly>
                    <span class="kbd">⌘K</span>
                </div>

                {{-- Language picker --}}
                <div class="sbl-tbar-lang" x-data="{ open: false }" @click.outside="open = false">
                    <button class="sbl-tbar-lang-btn" @click="open = !open" type="button">
                        <span class="sbl-tbar-lang-flag">{{ $currentLocaleData['flag'] }}</span>
                        <span class="sbl-tbar-lang-name">{{ $currentLocaleData['name'] }}</span>
                        <svg class="sbl-tbar-lang-chev" :class="open ? 'rot' : ''"
                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
                            <path d="M6 9l6 6 6-6"/>
                        </svg>
                    </button>
                    <div class="sbl-tbar-lang-drop"
                         x-show="open"
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1"
                         style="display:none">
                        @foreach($locales as $locale => $info)
                            <a href="{{ route('owner.locale.switch', ['locale' => $locale]) }}"
                               class="sbl-tbar-lang-opt {{ $currentLocale === $locale ? 'active' : '' }}">
                                <span class="lf">{{ $info['flag'] }}</span>
                                {{ $info['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Help icon --}}
                <a href="#" class="sbl-tbar-btn" aria-label="Help">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M9.5 9a2.5 2.5 0 015 0c0 2-2.5 2-2.5 4M12 18h.01"/>
                    </svg>
                </a>

                {{-- Avatar dropdown --}}
                <div class="sbl-av-wrap" x-data="{ open: false }" @click.outside="open = false">
                    <button class="sbl-av-btn" @click="open = !open" type="button" aria-label="Account menu">
                        @if($userAvatar)
                            <img src="{{ $userAvatar }}" alt="{{ $authUser->name }}" class="sbl-av-img">
                        @else
                            <span class="sbl-av-fallback">{{ $userInitial }}</span>
                        @endif
                    </button>

                    <div class="sbl-av-drop"
                         x-show="open"
                         x-cloak
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         x-transition:leave="transition ease-in duration-100"
                         x-transition:leave-start="opacity-100 translate-y-0"
                         x-transition:leave-end="opacity-0 translate-y-1">

                        <div class="sbl-av-user">
                            <div class="sbl-av-user-name">{{ $authUser->name }}</div>
                            <div class="sbl-av-user-email">{{ $authUser->email }}</div>
                        </div>
                        <div class="sbl-av-sep"></div>
                        <a href="{{ route('profile.edit') }}" class="sbl-av-item" @click="open = false">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                            </svg>
                            Profile
                        </a>
                        <div class="sbl-av-sep"></div>
                        @if(is_impersonating())
                            <a href="{{ route('impersonate.leave') }}" class="sbl-av-item sbl-av-item-danger">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 9l-7 7-7-7"/>
                                </svg>
                                {{ __('menu_owner.nav.leave_impersonation') }}
                            </a>
                        @else
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="sbl-av-item sbl-av-item-danger">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                                    </svg>
                                    {{ __('menu_owner.nav.log_out') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

            </div>
        </div>

        {{ $slot }}

    </div>
</div>

{{-- ── Delete confirmation modal ─────────────────────── --}}
<div x-data="{
         open: false, message: '', formId: null,
         confirm() { this.open = false; this.$nextTick(() => document.getElementById(this.formId)?.submit()); },
         cancel()  { this.open = false; },
     }"
     x-on:del-ask.window="message = $event.detail.message; formId = $event.detail.formId; open = true"
     x-show="open"
     x-cloak
     class="del-backdrop"
     @click.self="cancel()"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="del-modal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95">
        <div class="del-modal-ico">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M3 6h18M8 6V4h8v2M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
            </svg>
        </div>
        <p class="del-modal-title">{{ __('menu_owner.common.delete') }}</p>
        <p class="del-modal-msg" x-text="message"></p>
        <div class="del-modal-actions">
            <button type="button" class="ui-btn ui-btn-secondary" @click="cancel()">
                {{ __('menu_owner.common.cancel') }}
            </button>
            <button type="button" class="ui-btn ui-btn-danger" @click="confirm()">
                {{ __('menu_owner.common.delete') }}
            </button>
        </div>
    </div>
</div>

{{-- ── Toast notifications ────────────────────────────── --}}
<div x-data="{
         toasts: [],
         add(msg, type) {
             const id = Date.now();
             this.toasts.push({ id, msg, type });
             setTimeout(() => this.remove(id), 4500);
         },
         remove(id) { this.toasts = this.toasts.filter(t => t.id !== id); },
     }"
     x-on:toast.window="add($event.detail.message, $event.detail.type ?? 'success')"
     x-init="
         @if(session('success')) $nextTick(() => add(@js(session('success')), 'success')); @endif
         @if(session('error'))   $nextTick(() => add(@js(session('error')),   'error'));   @endif
     "
     class="toast-wrap">
    <template x-for="t in toasts" :key="t.id">
        <div class="toast" :class="'toast-' + t.type"
             x-transition:enter="ti-enter"
             x-transition:enter-start="ti-from"
             x-transition:enter-end="ti-to"
             x-transition:leave="ti-leave"
             x-transition:leave-start="ti-to"
             x-transition:leave-end="ti-from">
            <span class="toast-ico">
                <template x-if="t.type === 'success'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                </template>
                <template x-if="t.type === 'error'">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>
                    </svg>
                </template>
            </span>
            <span class="toast-msg" x-text="t.msg"></span>
            <button class="toast-close" type="button" @click="remove(t.id)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>

@stack('scripts')
</body>
</html>
