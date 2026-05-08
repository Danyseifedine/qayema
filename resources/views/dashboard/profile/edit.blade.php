<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ auth()->user()->restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ __('menu_owner.nav.profile') }}</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ __('menu_owner.nav.profile') }}</h1>
        </div>

        <div class="space-y-6">
            <div class="qp-card qp-card-body">
                <div class="max-w-xl">
                    @include('dashboard.profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="qp-card qp-card-body">
                <div class="max-w-xl">
                    @include('dashboard.profile.partials.update-password-form')
                </div>
            </div>

            <div class="qp-card qp-card-body">
                <div class="max-w-xl">
                    @include('dashboard.profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-sidebar-layout>
