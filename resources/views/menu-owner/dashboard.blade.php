<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">
            {{ __('menu_owner.dashboard.title') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            @if (!auth()->user()->isProfileComplete())
                @php
                    $currentStep = 1;
                @endphp
                <x-wizard-steps :currentStep="$currentStep" />
            @elseif(auth()->user()->menus()->count() == 0)
                @php
                    $currentStep = 2;
                @endphp
                <x-wizard-steps :currentStep="$currentStep" />
            @endif

            <div class="rounded-xl border border-slate-200/80 bg-white shadow-sm overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-100">
                    <h1 class="text-lg font-semibold text-slate-900">{{ __('menu_owner.dashboard.title') }}</h1>
                    <p class="mt-0.5 text-sm text-slate-500">Manage your menus, categories, and dishes in one place.</p>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="{{ route('menu-owner.statistics.index') }}" class="group flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4 transition hover:border-orange-200 hover:bg-orange-50/30">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 group-hover:bg-orange-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-slate-900">{{ __('menu_owner.nav.statistics') }}</span>
                                <p class="text-xs text-slate-500 mt-0.5">{{ __('menu_owner.statistics.title') }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-orange-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('menu-owner.menus.index') }}" class="group flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4 transition hover:border-orange-200 hover:bg-orange-50/30">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 group-hover:bg-orange-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-slate-900">{{ __('menu_owner.nav.menus') }}</span>
                                <p class="text-xs text-slate-500 mt-0.5">{{ __('menu_owner.menus.title') }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-orange-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('menu-owner.categories.index') }}" class="group flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4 transition hover:border-orange-200 hover:bg-orange-50/30">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 group-hover:bg-orange-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7a1.994 1.994 0 01-.586-1.414V7a4 4 0 014-4z"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-slate-900">{{ __('menu_owner.nav.categories') }}</span>
                                <p class="text-xs text-slate-500 mt-0.5">{{ __('menu_owner.categories.title') }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-orange-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('menu-owner.dishes.index') }}" class="group flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4 transition hover:border-orange-200 hover:bg-orange-50/30">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 group-hover:bg-orange-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-slate-900">{{ __('menu_owner.nav.dishes') }}</span>
                                <p class="text-xs text-slate-500 mt-0.5">{{ __('menu_owner.dishes.title') }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-orange-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('menu-owner.qr-code.index') }}" class="group flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4 transition hover:border-orange-200 hover:bg-orange-50/30">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 group-hover:bg-orange-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-slate-900">{{ __('menu_owner.nav.qr_code') }}</span>
                                <p class="text-xs text-slate-500 mt-0.5">{{ __('menu_owner.qr_code.title') }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-orange-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('menu-owner.settings.index') }}" class="group flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4 transition hover:border-orange-200 hover:bg-orange-50/30">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 group-hover:bg-orange-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-slate-900">{{ __('menu_owner.nav.settings') }}</span>
                                <p class="text-xs text-slate-500 mt-0.5">{{ __('menu_owner.settings.title') }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-orange-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('menu-owner.social-links.index') }}" class="group flex items-center gap-4 rounded-lg border border-slate-200 bg-slate-50/50 p-4 transition hover:border-orange-200 hover:bg-orange-50/30">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-orange-100 text-orange-600 group-hover:bg-orange-200">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="font-medium text-slate-900">{{ __('menu_owner.nav.social_links') }}</span>
                                <p class="text-xs text-slate-500 mt-0.5">{{ __('menu_owner.social_links.title') }}</p>
                            </div>
                            <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-orange-500 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
