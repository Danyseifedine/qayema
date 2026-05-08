<x-sidebar-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('dashboard') }}">{{ auth()->user()->restaurant?->name ?? __('menu_owner.nav.dashboard') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <a href="{{ route('menu-owner.social-links.index') }}">{{ __('menu_owner.nav.social_links') }}</a>
        <span class="sbl-crumb-sep">/</span>
        <span class="sbl-crumb-here">{{ $socialLink ? __('menu_owner.social_links.edit_social_link') : __('menu_owner.social_links.add_social_link') }}</span>
    </x-slot>

    <div class="sbl-content">
        <div class="sbl-page-head">
            <h1 class="sbl-page-title">{{ $socialLink ? __('menu_owner.social_links.edit_social_link') : __('menu_owner.social_links.add_social_link') }}</h1>
        </div>
        <p class="qf-desc">{{ __('menu_owner.social_links.form_desc') }}</p>

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg mb-6">{{ session('error') }}</div>
        @endif

        <div class="qf-section">
            <form method="POST"
                  action="{{ $socialLink ? route('menu-owner.social-links.update', $socialLink) : route('menu-owner.social-links.store') }}">
                @csrf
                @if ($socialLink) @method('PUT') @endif

                <div class="space-y-5">
                    <x-ui.field name="platform" label="{{ __('menu_owner.social_links.platform') }}"
                                help="{{ __('menu_owner.social_links.platform_desc') }}">
                        <x-ui.combo
                            name="platform"
                            :options="[
                                ['value' => 'instagram', 'label' => 'Instagram'],
                                ['value' => 'x',         'label' => 'X (Twitter)'],
                                ['value' => 'facebook',  'label' => 'Facebook'],
                                ['value' => 'tiktok',    'label' => 'TikTok'],
                            ]"
                            :value="old('platform', $socialLink?->platform ?? '')"
                            placeholder="{{ __('menu_owner.social_links.select_platform') }}" />
                    </x-ui.field>

                    <x-ui.field name="url" label="{{ __('menu_owner.social_links.url') }}"
                                help="{{ __('menu_owner.social_links.url_desc') }}">
                        <x-ui.input name="url" type="url"
                                    :value="old('url', $socialLink?->url)"
                                    placeholder="{{ __('menu_owner.social_links.placeholder_url') }}"
                                    required />
                    </x-ui.field>
                </div>

                <div class="qf-actions" style="margin-top:24px">
                    <x-btn href="{{ route('menu-owner.social-links.index') }}" variant="secondary">{{ __('menu_owner.common.cancel') }}</x-btn>
                    <x-btn type="submit" variant="primary">
                        {{ $socialLink ? __('menu_owner.common.update') : __('menu_owner.common.create') }}
                    </x-btn>
                </div>
            </form>
        </div>
    </div>
</x-sidebar-layout>
