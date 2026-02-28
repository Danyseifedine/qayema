<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $socialLink ? __('menu_owner.social_links.edit_social_link') : __('menu_owner.social_links.add_social_link') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST"
                        action="{{ $socialLink ? route('menu-owner.social-links.update', $socialLink) : route('menu-owner.social-links.store') }}">
                        @csrf
                        @if ($socialLink)
                            @method('PUT')
                        @endif

                        <!-- Platform -->
                        <div class="mb-6">
                            <x-input-label for="platform" :value="__('menu_owner.social_links.platform')" />
                            <p class="mt-0.5 text-sm text-gray-500">{{ __('menu_owner.social_links.platform_desc') }}</p>
                            <select id="platform" name="platform"
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                                required>
                                <option value="">{{ __('menu_owner.social_links.select_platform') }}</option>
                                <option value="instagram" {{ old('platform', $socialLink?->platform) === 'instagram' ? 'selected' : '' }}>
                                    Instagram
                                </option>
                                <option value="x" {{ old('platform', $socialLink?->platform) === 'x' ? 'selected' : '' }}>
                                    X (Twitter)
                                </option>
                                <option value="facebook" {{ old('platform', $socialLink?->platform) === 'facebook' ? 'selected' : '' }}>
                                    Facebook
                                </option>
                                <option value="tiktok" {{ old('platform', $socialLink?->platform) === 'tiktok' ? 'selected' : '' }}>
                                    TikTok
                                </option>
                            </select>
                            <x-input-error class="mt-2" :messages="$errors->get('platform')" />
                        </div>

                        <!-- URL -->
                        <div class="mb-6">
                            <x-input-label for="url" :value="__('menu_owner.social_links.url')" />
                            <x-text-input id="url" name="url" type="url" class="mt-1 block w-full"
                                :value="old('url', $socialLink?->url)" :placeholder="__('menu_owner.social_links.placeholder_url')" required />
                            <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.social_links.url_desc') }}</p>
                            <x-input-error class="mt-2" :messages="$errors->get('url')" />
                        </div>

                        <!-- Buttons -->
                        <div class="flex items-center gap-4">
                            <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ $socialLink ? __('menu_owner.common.update') : __('menu_owner.common.create') }}
                            </button>
                            <a href="{{ route('menu-owner.social-links.index') }}"
                                class="inline-flex items-center px-4 py-2 bg-gray-100 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                {{ __('menu_owner.common.cancel') }}
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

