@include('public.menu.partials.head')

<body class="antialiased bg-white">
    <!-- Simple Header -->
    <div class="border-b border-gray-200">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="text-center">
                @if ($settings['show_logo'] ?? true && $user->hasMedia('logo'))
                    <img src="{{ $user->getFirstMediaUrl('logo') }}" alt="Logo"
                        class="w-16 h-16 mx-auto mb-6 object-contain">
                @endif
                @if ($settings['show_restaurant_info'] ?? true)
                    <h1 class="text-4xl md:text-5xl font-light text-gray-900 mb-3 tracking-tight">
                        {{ $user->restaurant_name ?? 'Restaurant' }}
                    </h1>
                    @if ($menu->description)
                        <p class="text-gray-600 text-lg font-light max-w-2xl mx-auto">{{ $menu->description }}</p>
                    @endif
                @endif
            </div>

            @if ($settings['show_restaurant_info'] ?? true)
                <div class="flex flex-wrap items-center justify-center gap-6 mt-8 text-sm text-gray-500">
                    @if (($settings['show_phone_number'] ?? true) && $user->phone)
                        <span>{{ $user->phone }}</span>
                    @endif
                    @if (($settings['show_address'] ?? true) && $user->address)
                        <span>{{ $user->address }}</span>
                    @endif
                </div>

                @if (($settings['show_social_links'] ?? true) && $menu->socialLinks->isNotEmpty())
                    <div class="flex items-center justify-center gap-4 mt-6">
                        @foreach ($menu->socialLinks as $socialLink)
                            <a href="{{ $socialLink->url }}" target="_blank" rel="noopener noreferrer"
                                class="text-gray-400 hover:text-gray-600 transition-colors">
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
            @endif
        </div>
    </div>

    <!-- Menu Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
        @if ($categories->isNotEmpty())
            @foreach ($categories as $category)
                <div class="mb-20" id="category-{{ $category->id }}">
                    <!-- Category Header -->
                    <div class="mb-10 pb-4 border-b border-gray-200">
                        <div class="flex items-center gap-4">
                            @if (($settings['show_category_image'] ?? true) && $category->hasMedia('image'))
                                <img src="{{ $category->getFirstMediaUrl('image') }}" alt="{{ $category->name }}"
                                    class="w-12 h-12 object-cover">
                            @endif
                            <h2 class="text-3xl font-light text-gray-900 tracking-tight">{{ $category->name }}</h2>
                        </div>
                        @if ($category->description)
                            <p class="text-gray-500 mt-2 text-sm font-light">{{ $category->description }}</p>
                        @endif
                    </div>

                    <!-- Dishes List -->
                    @php
                        $dishes = $category->dishes->filter(function ($dish) {
                            return $dish->is_available === true;
                        });
                    @endphp
                    @if ($dishes->isNotEmpty())
                        <div class="space-y-8">
                            @foreach ($dishes as $dish)
                                <div class="flex flex-col md:flex-row gap-6 pb-8 border-b border-gray-100 last:border-0">
                                    @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                        <div class="flex-shrink-0 w-full md:w-32 h-32 overflow-hidden">
                                            <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                alt="{{ $dish->name }}" class="w-full h-full object-cover">
                                        </div>
                                    @endif

                                    <div class="flex-1">
                                        <div class="flex items-start justify-between mb-2">
                                            <h3 class="text-xl font-normal text-gray-900">{{ $dish->name }}</h3>
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
                                                <span class="text-lg font-light text-gray-700 ml-4 whitespace-nowrap">
                                                    @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                        {{ number_format($price, 2) }} {{ $currency }}
                                                    @else
                                                        ${{ number_format($price, 2) }}
                                                    @endif
                                                </span>
                                            @endif
                                        </div>

                                        @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                                            <p class="text-sm text-gray-500 font-light leading-relaxed">{{ $dish->ingredients }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <p class="text-gray-400 text-sm">No dishes available in this category yet.</p>
                        </div>
                    @endif
                </div>
            @endforeach

            <!-- Uncategorized Dishes -->
            @if ($uncategorizedDishes->isNotEmpty())
                <div class="mb-20" id="uncategorized">
                    <div class="mb-10 pb-4 border-b border-gray-200">
                        <h2 class="text-3xl font-light text-gray-900 tracking-tight">Other Items</h2>
                    </div>
                    <div class="space-y-8">
                        @foreach ($uncategorizedDishes as $dish)
                            <div class="flex flex-col md:flex-row gap-6 pb-8 border-b border-gray-100 last:border-0">
                                @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                    <div class="flex-shrink-0 w-full md:w-32 h-32 overflow-hidden">
                                        <img src="{{ $dish->getFirstMediaUrl('images') }}" alt="{{ $dish->name }}"
                                            class="w-full h-full object-cover">
                                    </div>
                                @endif
                                <div class="flex-1">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="text-xl font-normal text-gray-900">{{ $dish->name }}</h3>
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
                                            <span class="text-lg font-light text-gray-700 ml-4 whitespace-nowrap">
                                                @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                    {{ number_format($price, 2) }} {{ $currency }}
                                                @else
                                                    ${{ number_format($price, 2) }}
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                                        <p class="text-sm text-gray-500 font-light leading-relaxed">{{ $dish->ingredients }}</p>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-20">
                <p class="text-gray-400 text-sm">Menu coming soon.</p>
            </div>
        @endif
    </div>

    <!-- Footer -->
    <footer class="border-t border-gray-200 mt-20 py-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400 text-sm">&copy; {{ date('Y') }} {{ $user->restaurant_name ?? 'Restaurant' }}</p>
        </div>
    </footer>

    @include('public.menu.partials.scripts')
</body>

</html>

