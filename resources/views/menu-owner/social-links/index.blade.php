<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('menu_owner.social_links.title') }}
            </h2>
            @if ($menu)
                <x-btn href="{{ route('menu-owner.social-links.create') }}" variant="primary" size="sm" class="gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ __('menu_owner.social_links.add_social_link') }}
                </x-btn>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            @if (!$menu)
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6">
                    {{ __('menu_owner.social_links.create_menu_first') }}
                    <a href="{{ route('menu-owner.menus.index') }}" class="underline ms-2">{{ __('menu_owner.common.go_to_menu') }}</a>
                </div>
            @elseif($socialLinks->isEmpty())
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1">
                            </path>
                        </svg>
                        <h3 class="mt-2 text-sm font-semibold text-gray-900">{{ __('menu_owner.social_links.no_social_links') }}</h3>
                        <p class="mt-1 text-sm text-gray-500">{{ __('menu_owner.social_links.get_started') }}</p>
                        <div class="mt-6">
                            <x-btn href="{{ route('menu-owner.social-links.create') }}" variant="primary" size="sm" class="gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add Social Link
                            </x-btn>
                        </div>
                    </div>
                </div>
            @else
                @if ($menu)
                    <div class="mb-4 text-sm text-gray-600">
                        <span class="font-semibold">{{ $socialLinks->count() }}</span> / <span
                            class="font-semibold">{{ $menu->social_link_limit }}</span> social links
                        @if ($menu->hasReachedSocialLinkLimit())
                            <span class="ml-2 text-red-600 font-semibold">(Limit reached)</span>
                        @else
                            <span class="ml-2 text-green-600">({{ $menu->getRemainingSocialLinkSlots() }} remaining)</span>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($socialLinks as $socialLink)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                            <div class="p-6">
                                <div class="flex items-center gap-3 mb-4">
                                    @php
                                        $platformColors = [
                                            'instagram' => 'bg-gradient-to-br from-pink-500 to-purple-600',
                                            'x' => 'bg-sky-500',
                                            'facebook' => 'bg-blue-600',
                                            'tiktok' => 'bg-gray-900',
                                        ];
                                        $platformIcons = [
                                            'instagram' =>
                                                'M12.315 2c2.43.892 4.33 2.79 5.222 5.22a8.5 8.5 0 01-5.222 5.22 8.5 8.5 0 01-5.222-5.22A8.5 8.5 0 017.093 2h5.222zM12.315 7.093a3.5 3.5 0 110 7 3.5 3.5 0 010-7z',
                                            'x' =>
                                                'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
                                            'facebook' =>
                                                'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
                                            'tiktok' =>
                                                'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z',
                                        ];
                                        $platformNames = [
                                            'instagram' => 'Instagram',
                                            'x' => 'X (Twitter)',
                                            'facebook' => 'Facebook',
                                            'tiktok' => 'TikTok',
                                        ];
                                        $color = $platformColors[$socialLink->platform] ?? 'bg-gray-500';
                                        $icon = $platformIcons[$socialLink->platform] ?? '';
                                        $name = $platformNames[$socialLink->platform] ?? ucfirst($socialLink->platform);
                                    @endphp
                                    <div
                                        class="{{ $color }} w-12 h-12 rounded-lg flex items-center justify-center">
                                        @if ($socialLink->platform === 'instagram')
                                            <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor"
                                                stroke-width="2" viewBox="0 0 24 24">
                                                <rect x="2" y="2" width="20" height="20" rx="5"
                                                    ry="5" />
                                                <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z" />
                                                <line x1="17.5" y1="6.5" x2="17.51" y2="6.5" />
                                            </svg>
                                        @elseif ($socialLink->platform === 'x')
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                                            </svg>
                                        @elseif ($socialLink->platform === 'facebook')
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z" />
                                            </svg>
                                        @elseif ($socialLink->platform === 'tiktok')
                                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z" />
                                            </svg>
                                        @endif
                                    </div>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $name }}</h3>
                                    </div>
                                </div>

                                <p class="text-sm text-gray-600 mb-4 break-all">{{ $socialLink->url }}</p>

                                <div class="flex items-center gap-2">
                                    <x-btn href="{{ route('menu-owner.social-links.edit', $socialLink) }}" variant="secondary" size="sm" class="flex-1">Edit</x-btn>
                                    <form action="{{ route('menu-owner.social-links.destroy', $socialLink) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this social link?');"
                                        class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <x-btn type="submit" variant="danger" size="sm">Delete</x-btn>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
