@include('public.menu.partials.head')

<body class="antialiased bg-gradient-to-br from-gray-50 to-gray-100">
    <!-- Hero Section with Cover Image -->
    @if ($settings['show_logo'] ?? true && $user->hasMedia('cover_image'))
        <div class="relative h-80 md:h-[500px] overflow-hidden">
            <img src="{{ $user->getFirstMediaUrl('cover_image') }}" alt="Cover Image" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-8 md:p-12">
                <div class="max-w-7xl mx-auto">
                    @if ($settings['show_logo'] ?? true && $user->hasMedia('logo'))
                        <img src="{{ $user->getFirstMediaUrl('logo') }}" alt="Logo"
                            class="w-20 h-20 md:w-28 md:h-28 rounded-2xl object-contain bg-white/10 backdrop-blur-sm border-2 border-white/20 shadow-2xl mb-4">
                    @endif
                    @if ($settings['show_restaurant_info'] ?? true)
                        <h1 class="text-4xl md:text-6xl font-bold text-white mb-3 drop-shadow-lg">
                            {{ $user->restaurant_name ?? 'Restaurant' }}
                        </h1>
                        @if ($menu->description)
                            <p class="text-white/90 text-lg md:text-xl max-w-2xl drop-shadow-md">{{ $menu->description }}</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @else
        <div class="relative h-80 md:h-[500px] bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-600 overflow-hidden">
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-white px-4">
                    @if ($settings['show_logo'] ?? true && !$user->hasMedia('logo'))
                        <div class="w-32 h-32 md:w-40 md:h-40 rounded-3xl bg-white/20 backdrop-blur-sm border-4 border-white/30 flex items-center justify-center mx-auto mb-6 shadow-2xl">
                            <span class="text-white text-4xl md:text-6xl font-bold">
                                {{ strtoupper(substr($user->restaurant_name ?? 'R', 0, 1)) }}
                            </span>
                        </div>
                    @endif
                    @if ($settings['show_restaurant_info'] ?? true)
                        <h1 class="text-4xl md:text-6xl font-bold mb-3 drop-shadow-lg">{{ $user->restaurant_name ?? 'Restaurant' }}</h1>
                        @if ($menu->description)
                            <p class="text-white/90 text-lg md:text-xl max-w-2xl mx-auto drop-shadow-md">{{ $menu->description }}</p>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Info Bar -->
    @if ($settings['show_restaurant_info'] ?? true)
        <div class="bg-white/80 backdrop-blur-sm border-b border-gray-200 sticky top-0 z-50 shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
                    @if (($settings['show_phone_number'] ?? true) && $user->phone)
                        <div class="flex items-center gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                </path>
                            </svg>
                            <span class="font-medium">{{ $user->phone }}</span>
                        </div>
                    @endif
                    @if (($settings['show_address'] ?? true) && $user->address)
                        <div class="flex items-center gap-2 text-gray-700">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="font-medium">{{ $user->address }}</span>
                        </div>
                    @endif
                    @if (($settings['show_social_links'] ?? true) && $menu->socialLinks->isNotEmpty())
                        <div class="flex items-center gap-3">
                            @foreach ($menu->socialLinks as $socialLink)
                                @php
                                    $platformIcons = [
                                        'instagram' => 'M12.315 2c2.43.892 4.33 2.79 5.222 5.22a8.5 8.5 0 01-5.222 5.22 8.5 8.5 0 01-5.222-5.22A8.5 8.5 0 017.093 2h5.222zM12.315 7.093a3.5 3.5 0 110 7 3.5 3.5 0 010-7z',
                                        'x' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
                                        'facebook' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
                                        'tiktok' => 'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z',
                                    ];
                                    $platformColors = [
                                        'instagram' => 'text-pink-600 hover:text-pink-700',
                                        'x' => 'text-sky-600 hover:text-sky-700',
                                        'facebook' => 'text-blue-600 hover:text-blue-700',
                                        'tiktok' => 'text-gray-700 hover:text-gray-900',
                                    ];
                                    $icon = $platformIcons[$socialLink->platform] ?? '';
                                    $color = $platformColors[$socialLink->platform] ?? 'text-gray-600';
                                @endphp
                                <a href="{{ $socialLink->url }}" target="_blank" rel="noopener noreferrer"
                                    class="{{ $color }} transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="{{ $icon }}" />
                                    </svg>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <!-- Menu Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        @if ($categories->isNotEmpty())
            @foreach ($categories as $category)
                <div class="mb-20" id="category-{{ $category->id }}">
                    <!-- Category Header -->
                    <div class="mb-10 text-center">
                        <div class="inline-flex items-center gap-4 mb-4">
                            @if (($settings['show_category_image'] ?? true) && $category->hasMedia('image'))
                                <img src="{{ $category->getFirstMediaUrl('image') }}" alt="{{ $category->name }}"
                                    class="w-20 h-20 rounded-2xl object-cover shadow-lg ring-4 ring-white">
                            @elseif ($settings['show_category_image'] ?? true)
                                <div
                                    class="w-20 h-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-lg ring-4 ring-white">
                                    <span class="text-white text-2xl font-bold">
                                        {{ strtoupper(substr($category->name, 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                            <div>
                                <h2 class="text-4xl md:text-5xl font-bold text-gray-900">{{ $category->name }}</h2>
                                @if ($category->description)
                                    <p class="text-gray-600 mt-2 text-lg">{{ $category->description }}</p>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Dishes Grid -->
                    @php
                        $dishes = $category->dishes->filter(function ($dish) {
                            return $dish->is_available === true;
                        });
                    @endphp
                    @if ($dishes->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                            @foreach ($dishes as $dish)
                                <div
                                    class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100">
                                    @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                        <div class="relative h-56 overflow-hidden">
                                            <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                alt="{{ $dish->name }}" class="w-full h-full object-cover transition-transform duration-300 hover:scale-110">
                                        </div>
                                    @elseif ($settings['show_dish_image'] ?? true)
                                        <div
                                            class="relative h-56 bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                                            <svg class="w-20 h-20 text-indigo-300" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                    @endif

                                    <div class="p-6">
                                        <div class="flex items-start justify-between mb-3">
                                            <h3 class="text-2xl font-bold text-gray-900 flex-1">{{ $dish->name }}</h3>
                                            @if (($settings['show_prices'] ?? true) && $dish->price)
                                                @php
                                                    $price = $dish->price;
                                                    if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null)) {
                                                        $exchangeRate = (float) $settings['exchange_rate'];
                                                        $price = $price * $exchangeRate;
                                                        $currency = $settings['exchange_currency'] ?? 'USD';
                                                    } else {
                                                        $currency = 'USD';
                                                    }
                                                @endphp
                                                <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent ml-4">
                                                    @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                        {{ number_format($price, 2) }} {{ $currency }}
                                                    @else
                                                        ${{ number_format($price, 2) }}
                                                    @endif
                                                </span>
                                            @endif
                                        </div>

                                        @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                                            <div class="text-sm text-gray-600 leading-relaxed">
                                                <span>{{ $dish->ingredients }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-white rounded-2xl p-12 text-center shadow-lg">
                            <p class="text-gray-500 text-lg">No dishes available in this category yet.</p>
                        </div>
                    @endif
                </div>
            @endforeach

            <!-- Uncategorized Dishes -->
            @if ($uncategorizedDishes->isNotEmpty())
                <div class="mb-20" id="uncategorized">
                    <div class="mb-10 text-center">
                        <h2 class="text-4xl md:text-5xl font-bold text-gray-900">Other Items</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        @foreach ($uncategorizedDishes as $dish)
                            <div
                                class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-2xl transition-all duration-300 hover:-translate-y-1 border border-gray-100">
                                @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                    <div class="relative h-56 overflow-hidden">
                                        <img src="{{ $dish->getFirstMediaUrl('images') }}" alt="{{ $dish->name }}"
                                            class="w-full h-full object-cover transition-transform duration-300 hover:scale-110">
                                    </div>
                                @elseif ($settings['show_dish_image'] ?? true)
                                    <div
                                        class="relative h-56 bg-gradient-to-br from-indigo-100 to-purple-100 flex items-center justify-center">
                                        <svg class="w-20 h-20 text-indigo-300" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-3">
                                        <h3 class="text-2xl font-bold text-gray-900 flex-1">{{ $dish->name }}</h3>
                                        @if (($settings['show_prices'] ?? true) && $dish->price)
                                            @php
                                                $price = $dish->price;
                                                if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null)) {
                                                    $exchangeRate = (float) $settings['exchange_rate'];
                                                    $price = $price * $exchangeRate;
                                                    $currency = $settings['exchange_currency'] ?? 'USD';
                                                } else {
                                                    $currency = 'USD';
                                                }
                                            @endphp
                                            <span class="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-purple-600 bg-clip-text text-transparent ml-4">
                                                @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                    {{ number_format($price, 2) }} {{ $currency }}
                                                @else
                                                    ${{ number_format($price, 2) }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                                        <div class="text-sm text-gray-600 leading-relaxed">
                                            <span>{{ $dish->ingredients }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="bg-white rounded-2xl shadow-lg p-16 text-center">
                <svg class="w-32 h-32 mx-auto text-gray-300 mb-6" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <h3 class="text-3xl font-bold text-gray-900 mb-3">Menu Coming Soon</h3>
                <p class="text-gray-600 text-lg">Categories and dishes will appear here once they're added.</p>
            </div>
        @endif
    </div>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12 mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400">&copy; {{ date('Y') }} {{ $user->restaurant_name ?? 'Restaurant' }}. All rights reserved.</p>
        </div>
    </footer>

    @include('public.menu.partials.scripts')
</body>

</html>

