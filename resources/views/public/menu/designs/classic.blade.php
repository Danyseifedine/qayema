@include('public.menu.partials.head')

<body class="antialiased bg-amber-50">
    <!-- Classic Header with Elegant Border -->
    <div class="bg-white border-b-4 border-amber-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="text-center">
                @if ($settings['show_logo'] ?? true && $user->hasMedia('logo'))
                    <img src="{{ $user->getFirstMediaUrl('logo') }}" alt="Logo"
                        class="w-24 h-24 mx-auto mb-8 object-contain border-4 border-amber-800 p-2">
                @elseif ($settings['show_logo'] ?? true)
                    <div
                        class="w-24 h-24 mx-auto mb-8 bg-amber-800 border-4 border-amber-900 flex items-center justify-center">
                        <span class="text-white text-4xl font-serif font-bold">
                            {{ strtoupper(substr($user->restaurant_name ?? 'R', 0, 1)) }}
                        </span>
                    </div>
                @endif

                @if ($settings['show_restaurant_info'] ?? true)
                    <h1 class="text-5xl md:text-6xl font-serif font-bold text-amber-900 mb-4 tracking-wide">
                        {{ $user->restaurant_name ?? 'Restaurant' }}
                    </h1>
                    @if ($menu->description)
                        <p class="text-amber-700 text-lg font-serif italic max-w-3xl mx-auto mb-8">{{ $menu->description }}</p>
                    @endif
                @endif

                <div class="flex flex-wrap items-center justify-center gap-8 text-sm text-amber-800 font-serif">
                    @if (($settings['show_phone_number'] ?? true) && $user->phone)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                </path>
                            </svg>
                            <span class="font-semibold">{{ $user->phone }}</span>
                        </div>
                    @endif
                    @if (($settings['show_address'] ?? true) && $user->address)
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                </path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span class="font-semibold">{{ $user->address }}</span>
                        </div>
                    @endif
                </div>

                @if (($settings['show_social_links'] ?? true) && $menu->socialLinks->isNotEmpty())
                    <div class="flex items-center justify-center gap-4 mt-6">
                        @foreach ($menu->socialLinks as $socialLink)
                            <a href="{{ $socialLink->url }}" target="_blank" rel="noopener noreferrer"
                                class="w-10 h-10 border-2 border-amber-800 rounded-full flex items-center justify-center text-amber-800 hover:bg-amber-800 hover:text-white transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                    @php
                                        $platformIcons = [
                                            'instagram' => 'M12.315 2c2.43.892 4.33 2.79 5.222 5.22a8.5 8.5 0 01-5.222 5.22 8.5 8.5 0 01-5.222-5.22A8.5 8.5 0 017.093 2h5.222zM12.315 7.093a3.5 3.5 0 110 7 3.5 3.5 0 010-7z',
                                            'x' => 'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
                                            'facebook' => 'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
                                            'tiktok' => 'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z',
                                        ];
                                        $icon = $platformIcons[$socialLink->platform] ?? '';
                                    @endphp
                                    <path d="{{ $icon }}" />
                                </svg>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Menu Content -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        @if ($categories->isNotEmpty())
            @foreach ($categories as $category)
                <div class="mb-16" id="category-{{ $category->id }}">
                    <!-- Category Header -->
                    <div class="mb-8 text-center border-b-2 border-amber-800 pb-4">
                        <div class="flex items-center justify-center gap-4 mb-3">
                            @if (($settings['show_category_image'] ?? true) && $category->hasMedia('image'))
                                <img src="{{ $category->getFirstMediaUrl('image') }}" alt="{{ $category->name }}"
                                    class="w-16 h-16 object-cover border-2 border-amber-800 p-1">
                            @endif
                            <h2 class="text-4xl md:text-5xl font-serif font-bold text-amber-900">{{ $category->name }}</h2>
                        </div>
                        @if ($category->description)
                            <p class="text-amber-700 font-serif italic text-lg">{{ $category->description }}</p>
                        @endif
                    </div>

                    <!-- Dishes Grid -->
                    @php
                        $dishes = $category->dishes->filter(function ($dish) {
                            return $dish->is_available === true;
                        });
                    @endphp
                    @if ($dishes->isNotEmpty())
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            @foreach ($dishes as $dish)
                                <div class="bg-white border-2 border-amber-800 shadow-lg p-6 hover:shadow-xl transition-shadow">
                                    @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                        <div class="mb-4 border-2 border-amber-800 p-1">
                                            <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                alt="{{ $dish->name }}" class="w-full h-48 object-cover">
                                        </div>
                                    @endif

                                    <div class="flex items-start justify-between mb-3 border-b border-amber-200 pb-3">
                                        <h3 class="text-2xl font-serif font-bold text-amber-900 flex-1">{{ $dish->name }}</h3>
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
                                            <span class="text-2xl font-serif font-bold text-amber-800 ml-4 whitespace-nowrap">
                                                @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                    {{ number_format($price, 2) }} {{ $currency }}
                                                @else
                                                    ${{ number_format($price, 2) }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>

                                    @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                                        <p class="text-amber-700 font-serif text-sm leading-relaxed italic">{{ $dish->ingredients }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-white border-2 border-amber-200 p-12 text-center">
                            <p class="text-amber-600 font-serif">No dishes available in this category yet.</p>
                        </div>
                    @endif
                </div>
            @endforeach

            <!-- Uncategorized Dishes -->
            @if ($uncategorizedDishes->isNotEmpty())
                <div class="mb-16" id="uncategorized">
                    <div class="mb-8 text-center border-b-2 border-amber-800 pb-4">
                        <h2 class="text-4xl md:text-5xl font-serif font-bold text-amber-900">Other Items</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @foreach ($uncategorizedDishes as $dish)
                            <div class="bg-white border-2 border-amber-800 shadow-lg p-6 hover:shadow-xl transition-shadow">
                                @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                    <div class="mb-4 border-2 border-amber-800 p-1">
                                        <img src="{{ $dish->getFirstMediaUrl('images') }}" alt="{{ $dish->name }}"
                                            class="w-full h-48 object-cover">
                                    </div>
                                @endif
                                <div class="flex items-start justify-between mb-3 border-b border-amber-200 pb-3">
                                    <h3 class="text-2xl font-serif font-bold text-amber-900 flex-1">{{ $dish->name }}</h3>
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
                                        <span class="text-2xl font-serif font-bold text-amber-800 ml-4 whitespace-nowrap">
                                            @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                {{ number_format($price, 2) }} {{ $currency }}
                                            @else
                                                ${{ number_format($price, 2) }}
                                            @endif
                                        </span>
                                    @endif
                                </div>
                                @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                                    <p class="text-amber-700 font-serif text-sm leading-relaxed italic">{{ $dish->ingredients }}</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="bg-white border-2 border-amber-800 shadow-lg p-16 text-center">
                <svg class="w-32 h-32 mx-auto text-amber-300 mb-6" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                    </path>
                </svg>
                <h3 class="text-3xl font-serif font-bold text-amber-900 mb-3">Menu Coming Soon</h3>
                <p class="text-amber-700 font-serif text-lg">Categories and dishes will appear here once they're added.</p>
            </div>
        @endif
    </div>

    <!-- Footer -->
    <footer class="bg-amber-900 text-amber-100 py-10 mt-16 border-t-4 border-amber-800">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="font-serif">&copy; {{ date('Y') }} {{ $user->restaurant_name ?? 'Restaurant' }}. All rights reserved.</p>
        </div>
    </footer>

    @include('public.menu.partials.scripts')
</body>

</html>

