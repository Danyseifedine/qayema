<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      dir="{{ in_array(app()->getLocale(), config('locales.rtl', [])) ? 'rtl' : 'ltr' }}">
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

    $locales         = config('locales.locales');
    $currentLocale   = app()->getLocale();
    $preferredLocale = $restaurant?->preferred_language ?? $currentLocale;

    $userAvatar  = $authUser?->socialAccounts()->where('provider', 'google')->value('avatar');
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
                    {{ __('menu_owner.nav.menu_scan') }}
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
                <div x-data="cmdPalette()" @keydown.window="onKey($event)">

                    {{-- Trigger bar --}}
                    <button class="sbl-search" type="button" @click="open()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>
                        </svg>
                        <span class="sbl-search-placeholder">{{ __('menu_owner.search.placeholder') }}</span>
                        <span class="kbd">⌘K</span>
                    </button>

                    {{-- Backdrop --}}
                    <div class="cp-backdrop" x-show="isOpen" x-cloak @click="close()"
                         x-transition:enter="cp-fade-enter" x-transition:enter-start="cp-fade-start"
                         x-transition:enter-end="cp-fade-end" x-transition:leave="cp-fade-enter"
                         x-transition:leave-start="cp-fade-end" x-transition:leave-end="cp-fade-start">
                    </div>

                    {{-- Palette --}}
                    <div class="cp-modal" x-show="isOpen" x-cloak
                         x-transition:enter="cp-slide-enter" x-transition:enter-start="cp-slide-start"
                         x-transition:enter-end="cp-slide-end" x-transition:leave="cp-slide-enter"
                         x-transition:leave-start="cp-slide-end" x-transition:leave-end="cp-slide-start">

                        {{-- Input --}}
                        <div class="cp-input-wrap">
                            <svg class="cp-input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/>
                            </svg>
                            <input class="cp-input" type="text"
                                   x-model="query"
                                   x-ref="input"
                                   @input.debounce.250ms="fetch()"
                                   @keydown.arrow-down.prevent="move(1)"
                                   @keydown.arrow-up.prevent="move(-1)"
                                   @keydown.enter.prevent="go()"
                                   @keydown.escape.prevent="close()"
                                   placeholder="{{ __('menu_owner.search.input_placeholder') }}"
                                   autocomplete="off" spellcheck="false">
                            <button class="cp-esc-btn" type="button" @click="close()">Esc</button>
                        </div>

                        {{-- Results --}}
                        <div class="cp-results" x-ref="results">

                            {{-- Nav quick links (shown when query is empty) --}}
                            <template x-if="!query.trim()">
                                <div>
                                    <p class="cp-group-label">{{ __('menu_owner.search.quick_nav') }}</p>
                                    <template x-for="(item, i) in navItems" :key="i">
                                        <a :href="item.url" class="cp-item" :class="{ 'cp-item--active': cursor === i }"
                                           @mouseenter="cursor = i" @click="close()">
                                            <span class="cp-item-icon" x-html="item.icon"></span>
                                            <span class="cp-item-label" x-text="item.label"></span>
                                            <svg class="cp-item-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                        </a>
                                    </template>
                                </div>
                            </template>

                            {{-- Search results --}}
                            <template x-if="query.trim() && !loading">
                                <div>
                                    {{-- No results --}}
                                    <template x-if="!results.categories.length && !results.dishes.length">
                                        <p class="cp-empty">{{ __('menu_owner.search.no_results') }}</p>
                                    </template>

                                    {{-- Categories --}}
                                    <template x-if="results.categories.length">
                                        <div>
                                            <p class="cp-group-label">{{ __('menu_owner.search.group_categories') }}</p>
                                            <template x-for="(item, i) in results.categories" :key="'c'+i">
                                                <a :href="item.url" class="cp-item" :class="{ 'cp-item--active': cursor === (navItems.length + i) }"
                                                   @mouseenter="cursor = navItems.length + i" @click="close()">
                                                    <span class="cp-item-icon">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                                                    </span>
                                                    <span class="cp-item-label" x-text="item.name"></span>
                                                    <svg class="cp-item-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                                </a>
                                            </template>
                                        </div>
                                    </template>

                                    {{-- Dishes --}}
                                    <template x-if="results.dishes.length">
                                        <div>
                                            <p class="cp-group-label">{{ __('menu_owner.search.group_dishes') }}</p>
                                            <template x-for="(item, i) in results.dishes" :key="'d'+i">
                                                <a :href="item.url" class="cp-item" :class="{ 'cp-item--active': cursor === (navItems.length + results.categories.length + i) }"
                                                   @mouseenter="cursor = navItems.length + results.categories.length + i" @click="close()">
                                                    <span class="cp-item-icon">
                                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>
                                                    </span>
                                                    <span class="cp-item-label" x-text="item.name"></span>
                                                    <span x-show="!item.is_available" class="cp-item-badge">{{ __('menu_owner.common.unavailable') }}</span>
                                                    <svg class="cp-item-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                                </a>
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- Loading --}}
                            <template x-if="loading">
                                <div class="cp-loading">
                                    <svg class="cp-spinner" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
                                </div>
                            </template>
                        </div>
                    </div>

                    <script>
                    function cmdPalette() {
                        return {
                            isOpen: false,
                            query: '',
                            loading: false,
                            cursor: 0,
                            results: { dishes: [], categories: [] },
                            navItems: [
                                { label: '{{ __('menu_owner.nav.dashboard') }}',    url: '{{ route('dashboard') }}',                        icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>' },
                                { label: '{{ __('menu_owner.nav.dishes') }}',       url: '{{ route('menu-owner.dishes.index') }}',          icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>' },
                                { label: '{{ __('menu_owner.nav.categories') }}',   url: '{{ route('menu-owner.categories.index') }}',      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>' },
                                { label: '{{ __('menu_owner.nav.menu_scan') }}',    url: '{{ route('menu-owner.menu-scan.index') }}',       icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>' },
                                { label: '{{ __('menu_owner.nav.statistics') }}',   url: '{{ route('menu-owner.statistics.index') }}',      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>' },
                                { label: '{{ __('menu_owner.nav.qr_code') }}',      url: '{{ route('menu-owner.qr-code.index') }}',         icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="5" height="5"/><rect x="16" y="3" width="5" height="5"/><rect x="3" y="16" width="5" height="5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3M21 21v-1M16 11h5M11 3v5M11 11v1M11 16v1M11 21v-4M6 11H3M16 16h-5"/></svg>' },
                                { label: '{{ __('menu_owner.nav.social_links') }}', url: '{{ route('menu-owner.social-links.index') }}',    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>' },
                                { label: '{{ __('menu_owner.nav.restaurant') }}',   url: '{{ route('menu-owner.restaurant.index') }}',      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>' },
                                { label: '{{ __('menu_owner.nav.settings') }}',     url: '{{ route('menu-owner.settings.index') }}',        icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>' },
                            ],

                            open() {
                                this.isOpen = true;
                                this.query = '';
                                this.results = { dishes: [], categories: [] };
                                this.cursor = 0;
                                this.$nextTick(() => this.$refs.input.focus());
                            },

                            close() {
                                this.isOpen = false;
                                this.query = '';
                            },

                            onKey(e) {
                                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                                    e.preventDefault();
                                    this.isOpen ? this.close() : this.open();
                                }
                            },

                            async fetch() {
                                const q = this.query.trim();
                                if (q.length < 1) {
                                    this.results = { dishes: [], categories: [] };
                                    this.cursor = 0;
                                    return;
                                }
                                this.loading = true;
                                try {
                                    const res = await fetch(`{{ route('menu-owner.search') }}?q=${encodeURIComponent(q)}`, {
                                        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                                    });
                                    const data = await res.json();
                                    // Attach edit URLs
                                    data.categories = (data.categories || []).map(c => ({ ...c, url: `{{ url('category-edit') }}/` + c.id }));
                                    data.dishes     = (data.dishes || []).map(d => ({ ...d, url: `{{ url('dish-edit') }}/` + d.id }));
                                    this.results = data;
                                    this.cursor = 0;
                                } finally {
                                    this.loading = false;
                                }
                            },

                            allItems() {
                                if (!this.query.trim()) return this.navItems;
                                return [...this.results.categories, ...this.results.dishes];
                            },

                            move(dir) {
                                const total = this.query.trim()
                                    ? this.results.categories.length + this.results.dishes.length
                                    : this.navItems.length;
                                if (!total) return;
                                this.cursor = (this.cursor + dir + total) % total;
                                this.$nextTick(() => {
                                    const active = this.$refs.results?.querySelector('.cp-item--active');
                                    active?.scrollIntoView({ block: 'nearest' });
                                });
                            },

                            go() {
                                const items = this.query.trim()
                                    ? [...this.results.categories, ...this.results.dishes]
                                    : this.navItems;
                                const item = items[this.cursor];
                                if (item?.url) { window.location.href = item.url; this.close(); }
                            },
                        };
                    }
                    </script>
                </div>

                {{-- Language picker --}}
                <div class="lang-picker" x-data="{ open: false }" @click.outside="open = false">
                    <button class="lang-trigger" @click="open = !open" :aria-expanded="open" type="button">
                        <span>{{ $locales[$currentLocale]['flag'] ?? '' }}</span>
                        <span>{{ strtoupper($currentLocale) }}</span>
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
                            <a class="lang-option {{ $currentLocale === $code ? 'active' : '' }}"
                               href="{{ route('owner.locale.switch', $code) }}">
                                <span>{{ $info['flag'] }}</span>
                                <span>{{ $info['name'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- Help guide --}}
                <div x-data="helpGuide()">
                    <button type="button" class="sbl-tbar-btn" aria-label="Help" @click="open = true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M9.5 9a2.5 2.5 0 015 0c0 2-2.5 2-2.5 4M12 18h.01"/>
                        </svg>
                    </button>

                    {{-- Backdrop --}}
                    <div class="hg-backdrop" x-show="open" x-cloak @click="open = false"
                         x-transition:enter="hg-fade" x-transition:enter-start="hg-fade-out"
                         x-transition:enter-end="hg-fade-in" x-transition:leave="hg-fade"
                         x-transition:leave-start="hg-fade-in" x-transition:leave-end="hg-fade-out">
                    </div>

                    {{-- Drawer --}}
                    <div class="hg-drawer" x-show="open" x-cloak
                         x-transition:enter="hg-slide" x-transition:enter-start="hg-slide-out"
                         x-transition:enter-end="hg-slide-in" x-transition:leave="hg-slide"
                         x-transition:leave-start="hg-slide-in" x-transition:leave-end="hg-slide-out">

                        {{-- Header --}}
                        <div class="hg-header">
                            <div class="hg-header-left">
                                <div class="hg-header-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"/><path d="M9.5 9a2.5 2.5 0 015 0c0 2-2.5 2-2.5 4M12 18h.01"/>
                                    </svg>
                                </div>
                                <div>
                                    <p class="hg-header-title">{{ __('menu_owner.help.title') }}</p>
                                    <p class="hg-header-sub">{{ __('menu_owner.help.subtitle') }}</p>
                                </div>
                            </div>
                            <button type="button" class="hg-close" @click="open = false">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
                            </button>
                        </div>

                        {{-- Sections --}}
                        <div class="hg-body">
                            <template x-for="(section, i) in sections" :key="i">
                                <div class="hg-section" :class="{ 'hg-section--open': active === i }">
                                    <button type="button" class="hg-section-btn" @click="active = active === i ? null : i">
                                        <span class="hg-section-icon" x-html="section.icon"></span>
                                        <span class="hg-section-title" x-text="section.title"></span>
                                        <svg class="hg-chevron" :class="{ 'hg-chevron--open': active === i }"
                                             viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round">
                                            <path d="M6 9l6 6 6-6"/>
                                        </svg>
                                    </button>
                                    <div class="hg-content" x-show="active === i" x-collapse>
                                        <ul class="hg-steps">
                                            <template x-for="(step, si) in section.steps" :key="si">
                                                <li class="hg-step">
                                                    <span class="hg-step-num" x-text="si + 1"></span>
                                                    <span class="hg-step-text" x-text="step"></span>
                                                </li>
                                            </template>
                                        </ul>
                                        <template x-if="section.tip">
                                            <div class="hg-tip">
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/></svg>
                                                <span x-text="section.tip"></span>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </div>

                        {{-- Footer --}}
                        <div class="hg-footer">
                            <p>{!! __('menu_owner.help.footer', ['link' => '<a href="mailto:support@menux.app">'.__('menu_owner.help.footer_link').'</a>']) !!}</p>
                        </div>
                    </div>

                    <script>
                    function helpGuide() {
                        return {
                            open: false,
                            active: 0,
                            sections: [
                                {
                                    title: @js(__('menu_owner.help.getting_started.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>',
                                    steps: [@js(__('menu_owner.help.getting_started.step_1')),@js(__('menu_owner.help.getting_started.step_2')),@js(__('menu_owner.help.getting_started.step_3')),@js(__('menu_owner.help.getting_started.step_4')),@js(__('menu_owner.help.getting_started.step_5'))],
                                    tip: @js(__('menu_owner.help.getting_started.tip')),
                                },
                                {
                                    title: @js(__('menu_owner.help.dashboard.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
                                    steps: [@js(__('menu_owner.help.dashboard.step_1')),@js(__('menu_owner.help.dashboard.step_2')),@js(__('menu_owner.help.dashboard.step_3')),@js(__('menu_owner.help.dashboard.step_4')),@js(__('menu_owner.help.dashboard.step_5'))],
                                    tip: null,
                                },
                                {
                                    title: @js(__('menu_owner.help.dishes.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/><line x1="6" y1="1" x2="6" y2="4"/><line x1="10" y1="1" x2="10" y2="4"/><line x1="14" y1="1" x2="14" y2="4"/></svg>',
                                    steps: [@js(__('menu_owner.help.dishes.step_1')),@js(__('menu_owner.help.dishes.step_2')),@js(__('menu_owner.help.dishes.step_3')),@js(__('menu_owner.help.dishes.step_4')),@js(__('menu_owner.help.dishes.step_5')),@js(__('menu_owner.help.dishes.step_6'))],
                                    tip: @js(__('menu_owner.help.dishes.tip')),
                                },
                                {
                                    title: @js(__('menu_owner.help.categories.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>',
                                    steps: [@js(__('menu_owner.help.categories.step_1')),@js(__('menu_owner.help.categories.step_2')),@js(__('menu_owner.help.categories.step_3')),@js(__('menu_owner.help.categories.step_4')),@js(__('menu_owner.help.categories.step_5'))],
                                    tip: null,
                                },
                                {
                                    title: @js(__('menu_owner.help.ai_scanner.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
                                    steps: [@js(__('menu_owner.help.ai_scanner.step_1')),@js(__('menu_owner.help.ai_scanner.step_2')),@js(__('menu_owner.help.ai_scanner.step_3')),@js(__('menu_owner.help.ai_scanner.step_4')),@js(__('menu_owner.help.ai_scanner.step_5'))],
                                    tip: @js(__('menu_owner.help.ai_scanner.tip')),
                                },
                                {
                                    title: @js(__('menu_owner.help.qr_code.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="3" y="3" width="5" height="5"/><rect x="16" y="3" width="5" height="5"/><rect x="3" y="16" width="5" height="5"/><path d="M21 16h-3a2 2 0 0 0-2 2v3M21 21v-1M16 11h5M11 3v5M11 11v1"/></svg>',
                                    steps: [@js(__('menu_owner.help.qr_code.step_1')),@js(__('menu_owner.help.qr_code.step_2')),@js(__('menu_owner.help.qr_code.step_3')),@js(__('menu_owner.help.qr_code.step_4')),@js(__('menu_owner.help.qr_code.step_5'))],
                                    tip: @js(__('menu_owner.help.qr_code.tip')),
                                },
                                {
                                    title: @js(__('menu_owner.help.statistics.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
                                    steps: [@js(__('menu_owner.help.statistics.step_1')),@js(__('menu_owner.help.statistics.step_2')),@js(__('menu_owner.help.statistics.step_3')),@js(__('menu_owner.help.statistics.step_4')),@js(__('menu_owner.help.statistics.step_5'))],
                                    tip: @js(__('menu_owner.help.statistics.tip')),
                                },
                                {
                                    title: @js(__('menu_owner.help.social_links.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>',
                                    steps: [@js(__('menu_owner.help.social_links.step_1')),@js(__('menu_owner.help.social_links.step_2')),@js(__('menu_owner.help.social_links.step_3')),@js(__('menu_owner.help.social_links.step_4')),@js(__('menu_owner.help.social_links.step_5'))],
                                    tip: @js(__('menu_owner.help.social_links.tip')),
                                },
                                {
                                    title: @js(__('menu_owner.help.restaurant.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
                                    steps: [@js(__('menu_owner.help.restaurant.step_1')),@js(__('menu_owner.help.restaurant.step_2')),@js(__('menu_owner.help.restaurant.step_3')),@js(__('menu_owner.help.restaurant.step_4')),@js(__('menu_owner.help.restaurant.step_5'))],
                                    tip: null,
                                },
                                {
                                    title: @js(__('menu_owner.help.display.title')),
                                    icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
                                    steps: [@js(__('menu_owner.help.display.step_1')),@js(__('menu_owner.help.display.step_2')),@js(__('menu_owner.help.display.step_3')),@js(__('menu_owner.help.display.step_4')),@js(__('menu_owner.help.display.step_5')),@js(__('menu_owner.help.display.step_6'))],
                                    tip: @js(__('menu_owner.help.display.tip')),
                                },
                            ],
                        };
                    }
                    </script>
                </div>

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
