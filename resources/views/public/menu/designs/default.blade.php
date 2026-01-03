@include('public.menu.partials.head')

@php
    // Helper function to get price position classes
    function getPricePositionClasses($position)
    {
        $positionClasses = [
            'bottom_left' => 'bottom-3 left-3',
            'bottom_right' => 'bottom-3 right-3',
            'top_left' => 'top-3 left-3',
            'top_right' => 'top-3 right-3',
        ];
        return $positionClasses[$position] ?? 'bottom-3 right-3';
    }

    $pricePosition = $settings['price_position'] ?? 'bottom_right';
    $categoryCollapsible = $settings['category_collapsible'] ?? true;
    $defaultState = $settings['category_default_state'] ?? 'open';

    // Build category states for Alpine.js (only needed if collapsible)
    $categoryStates = [];
    if ($categoryCollapsible) {
        foreach ($categories as $category) {
            $categoryStates[(string) $category->id] = $defaultState === 'open';
        }
    }
@endphp

<body class="antialiased bg-gray-50">
    <div x-data="{ categoryStates: {{ json_encode($categoryStates) }}, toggleCategory(id) { this.categoryStates[String(id)] = !this.categoryStates[String(id)]; } }">

        @php
            $showCoverImage = ($settings['show_cover_image'] ?? true) && $user->hasMedia('cover_image');
        @endphp

        <!-- Cover Image Section -->
        @if ($showCoverImage)
            <div class="relative h-64 md:h-96 overflow-hidden">
                <img src="{{ $user->getFirstMediaUrl('cover_image') }}" alt="Cover Image"
                    class="w-full h-full object-cover">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
            </div>
        @endif

        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 {{ $showCoverImage ? '-mt-16 md:-mt-24' : 'pt-8' }} relative z-10">
            <!-- Logo and Restaurant Info Card -->
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="p-6 md:p-8">
                    <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                        <!-- Logo -->
                        @if ($settings['show_logo'] ?? true)
                            <div class="flex-shrink-0">
                                @if ($user->hasMedia('logo'))
                                    <img src="{{ $user->getFirstMediaUrl('logo') }}" alt="Logo"
                                        class="w-24 h-24 md:w-32 md:h-32 rounded-full object-contain border-4 border-white shadow-lg">
                                @else
                                    <div
                                        class="w-24 h-24 md:w-32 md:h-32 rounded-full bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center border-4 border-white shadow-lg">
                                        <span class="text-white text-2xl md:text-4xl font-bold">
                                            {{ strtoupper(substr($user->restaurant_name ?? 'R', 0, 1)) }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <!-- Restaurant Info -->
                        @if ($settings['show_restaurant_info'] ?? true)
                            <div class="flex-1 text-center md:text-left">
                                <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                                    {{ $user->restaurant_name ?? 'Restaurant' }}
                                </h1>
                                @if ($menu->description)
                                    <p class="text-gray-600 mb-4 text-sm text-justify">{{ $menu->description }}</p>
                                @endif

                                <div class="flex flex-wrap gap-4 justify-center md:justify-start text-sm text-gray-600">
                                    @if (($settings['show_phone_number'] ?? true) && $user->phone)
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                                </path>
                                            </svg>
                                            <span>{{ $user->phone }}</span>
                                        </div>
                                    @endif
                                    @if (($settings['show_address'] ?? true) && $user->address)
                                        <div class="flex items-center gap-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                                </path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            <span>{{ $user->address }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Social Links -->
                                @if (($settings['show_social_links'] ?? true) && $menu->socialLinks->isNotEmpty())
                                    <div class="flex flex-wrap gap-3 justify-center md:justify-start mt-4">
                                        @foreach ($menu->socialLinks as $socialLink)
                                            @php
                                                $platformIcons = [
                                                    'instagram' =>
                                                        'M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z',
                                                    'x' =>
                                                        'M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z',
                                                    'facebook' =>
                                                        'M18 2h-3a5 5 0 00-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 011-1h3z',
                                                    'tiktok' =>
                                                        'M19.59 6.69a4.83 4.83 0 01-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 01-5.2 1.74 2.89 2.89 0 012.31-4.64 2.93 2.93 0 01.88.13V9.4a6.84 6.84 0 00-1-.05A6.33 6.33 0 005 20.1a6.34 6.34 0 0010.86-4.43v-7a8.16 8.16 0 004.77 1.52v-3.4a4.85 4.85 0 01-1-.1z',
                                                ];
                                                $platformColors = [
                                                    'instagram' => 'bg-gradient-to-br from-pink-500 to-purple-600',
                                                    'x' => 'bg-sky-500',
                                                    'facebook' => 'bg-blue-600',
                                                    'tiktok' => 'bg-gray-900',
                                                ];
                                                $icon = $platformIcons[$socialLink->platform] ?? '';
                                                $color = $platformColors[$socialLink->platform] ?? 'bg-gray-500';
                                            @endphp
                                            <a href="{{ $socialLink->url }}" target="_blank" rel="noopener noreferrer"
                                                class="{{ $color }} w-10 h-10 rounded-full flex items-center justify-center text-white hover:opacity-90 transition-opacity">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="{{ $icon }}" />
                                                </svg>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Content -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            @if ($categories->isNotEmpty())
                <div class="space-y-24">
                    @foreach ($categories as $category)
                        <div id="category-{{ $category->id }}">
                            <!-- Category Header -->
                            <div class="mb-8">
                                @if ($categoryCollapsible)
                                    <button @click="toggleCategory({{ $category->id }})" type="button"
                                        class="w-full text-left">
                                @endif
                                <div
                                    class="flex items-center mt-12 gap-4 {{ $categoryCollapsible ? 'cursor-pointer hover:opacity-80 transition-opacity' : '' }}">
                                    @if (($settings['show_category_image'] ?? true) && $category->hasMedia('image'))
                                        <img src="{{ $category->getFirstMediaUrl('image') }}"
                                            alt="{{ $category->name }}"
                                            class="w-16 h-16 rounded-lg object-cover shadow-md flex-shrink-0">
                                    @elseif ($settings['show_category_image'] ?? true)
                                        <div
                                            class="w-16 h-16 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-md flex-shrink-0">
                                            <span class="text-white text-xl font-bold">
                                                {{ strtoupper(substr($category->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h2 class="text-lg font-bold text-gray-900">{{ $category->name }}</h2>
                                        @if ($category->description)
                                            <p class="text-gray-600 mt-1 text-xs text-justify">
                                                {{ $category->description }}</p>
                                        @endif
                                    </div>
                                    @if ($categoryCollapsible)
                                        <!-- Plus/Minus Icon - Far Right -->
                                        <div
                                            class="flex-shrink-0 w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center transition-colors duration-200 hover:bg-gray-200">
                                            <!-- Plus Icon (shown when closed) -->
                                            <svg x-show="categoryStates['{{ $category->id }}'] !== true"
                                                class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v12m6-6H6"></path>
                                            </svg>
                                            <!-- Minus Icon (shown when open) -->
                                            <svg x-show="categoryStates['{{ $category->id }}'] === true" x-cloak
                                                class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M18 12H6"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </div>
                                @if ($categoryCollapsible)
                                    </button>
                                @endif
                            </div>

                            <!-- Dishes Grid -->
                            <div
                                @if ($categoryCollapsible) x-show="categoryStates['{{ $category->id }}'] === true"
                         x-transition:enter="transition ease-out duration-300"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100 transform translate-y-0"
                         x-transition:leave-end="opacity-0 transform -translate-y-2" @endif>
                                @php
                                    $dishes = $category->dishes->filter(function ($dish) {
                                        return $dish->is_available === true;
                                    });
                                @endphp
                                @if ($dishes->isNotEmpty())
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                        @foreach ($dishes as $dish)
                                            <div
                                                class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                                                <!-- Dish Image -->
                                                @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                                    <div class="relative h-48 overflow-hidden">
                                                        <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                            alt="{{ $dish->name }}"
                                                            class="w-full h-full object-cover">
                                                        @if (($settings['show_prices'] ?? true) && $dish->price && $pricePosition !== 'next_to_title')
                                                            @php
                                                                $price = $dish->price;
                                                                if (
                                                                    ($settings['currency_enabled'] ?? false) &&
                                                                    ($settings['exchange_rate'] ?? null)
                                                                ) {
                                                                    $exchangeRate = (float) $settings['exchange_rate'];
                                                                    $price = $price * $exchangeRate;
                                                                    $currency = $settings['exchange_currency'] ?? 'USD';
                                                                    $formattedPrice = number_format(
                                                                        $price,
                                                                        0,
                                                                        '.',
                                                                        ',',
                                                                    );
                                                                } else {
                                                                    $currency = 'USD';
                                                                    $formattedPrice = number_format($price, 2);
                                                                }
                                                            @endphp
                                                            <div
                                                                class="absolute {{ getPricePositionClasses($pricePosition) }} bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow-lg border border-white/50">
                                                                <span class="text-sm font-bold text-indigo-600">
                                                                    @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                                        {{ $formattedPrice }} {{ $currency }}
                                                                    @else
                                                                        ${{ $formattedPrice }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @elseif ($settings['show_dish_image'] ?? true)
                                                    <div
                                                        class="relative h-48 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                                        <svg class="w-16 h-16 text-gray-400" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2"
                                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                            </path>
                                                        </svg>
                                                        @if (($settings['show_prices'] ?? true) && $dish->price && $pricePosition !== 'next_to_title')
                                                            @php
                                                                $price = $dish->price;
                                                                if (
                                                                    ($settings['currency_enabled'] ?? false) &&
                                                                    ($settings['exchange_rate'] ?? null)
                                                                ) {
                                                                    $exchangeRate = (float) $settings['exchange_rate'];
                                                                    $price = $price * $exchangeRate;
                                                                    $currency = $settings['exchange_currency'] ?? 'USD';
                                                                    $formattedPrice = number_format(
                                                                        $price,
                                                                        0,
                                                                        '.',
                                                                        ',',
                                                                    );
                                                                } else {
                                                                    $currency = 'USD';
                                                                    $formattedPrice = number_format($price, 2);
                                                                }
                                                            @endphp
                                                            <div
                                                                class="absolute {{ getPricePositionClasses($pricePosition) }} bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow-lg border border-white/50">
                                                                <span class="text-sm font-bold text-indigo-600">
                                                                    @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                                        {{ $formattedPrice }} {{ $currency }}
                                                                    @else
                                                                        ${{ $formattedPrice }}
                                                                    @endif
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                                <!-- Dish Info -->
                                                <div class="p-6">
                                                    <div class="flex items-start justify-between mb-2 gap-4">
                                                        <h3
                                                            class="text-lg font-bold text-gray-900 flex-1 min-w-0 pr-4">
                                                            {{ $dish->name }}</h3>
                                                        @if (
                                                            ($settings['show_prices'] ?? true) &&
                                                                $dish->price &&
                                                                ($settings['price_position'] ?? 'bottom_right') === 'next_to_title')
                                                            @php
                                                                $price = $dish->price;
                                                                if (
                                                                    ($settings['currency_enabled'] ?? false) &&
                                                                    ($settings['exchange_rate'] ?? null)
                                                                ) {
                                                                    $exchangeRate = (float) $settings['exchange_rate'];
                                                                    $price = $price * $exchangeRate;
                                                                    $currency = $settings['exchange_currency'] ?? 'USD';
                                                                    $formattedPrice = number_format(
                                                                        $price,
                                                                        0,
                                                                        '.',
                                                                        ',',
                                                                    );
                                                                } else {
                                                                    $currency = 'USD';
                                                                    $formattedPrice = number_format($price, 2);
                                                                }
                                                            @endphp
                                                            <span
                                                                class="text-sm font-bold text-indigo-600 whitespace-nowrap flex-shrink-0">
                                                                @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                                    {{ $formattedPrice }} {{ $currency }}
                                                                @else
                                                                    ${{ $formattedPrice }}
                                                                @endif
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <!-- Dish Details -->
                                                    @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                                                        <div class="text-xs text-gray-500 mt-2">
                                                            <span>{{ $dish->ingredients }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="bg-gray-50 rounded-lg p-8 text-center">
                                        <p class="text-gray-500">No dishes available in this category yet.</p>
                                    </div>
                                @endif
                            </div>
                    @endforeach
                </div>

                <!-- Uncategorized Dishes -->
                @if ($uncategorizedDishes->isNotEmpty())
                    <div class="mb-12" id="uncategorized">
                        <div class="mb-6">
                            <h2 class="text-lg font-bold text-gray-900">Other Items</h2>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($uncategorizedDishes as $dish)
                                <div
                                    class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                                    @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                        <div class="relative h-48 overflow-hidden">
                                            <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                alt="{{ $dish->name }}" class="w-full h-full object-cover">
                                            @if (($settings['show_prices'] ?? true) && $dish->price && $pricePosition !== 'next_to_title')
                                                @php
                                                    $price = $dish->price;
                                                    if (
                                                        ($settings['currency_enabled'] ?? false) &&
                                                        ($settings['exchange_rate'] ?? null)
                                                    ) {
                                                        $exchangeRate = (float) $settings['exchange_rate'];
                                                        $price = $price * $exchangeRate;
                                                        $currency = $settings['exchange_currency'] ?? 'USD';
                                                        $formattedPrice = number_format($price, 0, '.', ',');
                                                    } else {
                                                        $currency = 'USD';
                                                        $formattedPrice = number_format($price, 2);
                                                    }
                                                @endphp
                                                <div
                                                    class="absolute {{ getPricePositionClasses($pricePosition) }} bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow-lg border border-white/50">
                                                    <span class="text-sm font-bold text-indigo-600">
                                                        @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                            {{ $formattedPrice }} {{ $currency }}
                                                        @else
                                                            ${{ $formattedPrice }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @elseif ($settings['show_dish_image'] ?? true)
                                        <div
                                            class="relative h-48 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                            @if (($settings['show_prices'] ?? true) && $dish->price && $pricePosition !== 'next_to_title')
                                                @php
                                                    $price = $dish->price;
                                                    if (
                                                        ($settings['currency_enabled'] ?? false) &&
                                                        ($settings['exchange_rate'] ?? null)
                                                    ) {
                                                        $exchangeRate = (float) $settings['exchange_rate'];
                                                        $price = $price * $exchangeRate;
                                                        $currency = $settings['exchange_currency'] ?? 'USD';
                                                        $formattedPrice = number_format($price, 0, '.', ',');
                                                    } else {
                                                        $currency = 'USD';
                                                        $formattedPrice = number_format($price, 2);
                                                    }
                                                @endphp
                                                <div
                                                    class="absolute {{ getPricePositionClasses($pricePosition) }} bg-white/95 backdrop-blur-sm px-3 py-1.5 rounded-lg shadow-lg border border-white/50">
                                                    <span class="text-sm font-bold text-indigo-600">
                                                        @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                            {{ $formattedPrice }} {{ $currency }}
                                                        @else
                                                            ${{ $formattedPrice }}
                                                        @endif
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    @endif
                                    <div class="p-6">
                                        <div class="flex items-start justify-between mb-2 gap-4">
                                            <h3 class="text-lg font-bold text-gray-900 flex-1 min-w-0 pr-4">
                                                {{ $dish->name }}</h3>
                                            @if (($settings['show_prices'] ?? true) && $dish->price && $pricePosition === 'next_to_title')
                                                @php
                                                    $price = $dish->price;
                                                    if (
                                                        ($settings['currency_enabled'] ?? false) &&
                                                        ($settings['exchange_rate'] ?? null)
                                                    ) {
                                                        $exchangeRate = (float) $settings['exchange_rate'];
                                                        $price = $price * $exchangeRate;
                                                        $currency = $settings['exchange_currency'] ?? 'USD';
                                                        $formattedPrice = number_format($price, 0, '.', ',');
                                                    } else {
                                                        $currency = 'USD';
                                                        $formattedPrice = number_format($price, 2);
                                                    }
                                                @endphp
                                                <span
                                                    class="text-sm font-bold text-indigo-600 whitespace-nowrap flex-shrink-0">
                                                    @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                        {{ $formattedPrice }} {{ $currency }}
                                                    @else
                                                        ${{ $formattedPrice }}
                                                    @endif
                                                </span>
                                            @endif
                                        </div>
                                        @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                                            <div class="text-xs text-gray-500 mt-2">
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
                <!-- No Categories -->
                <div class="bg-white rounded-lg shadow-md p-12 text-center">
                    <svg class="w-24 h-24 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Menu Coming Soon</h3>
                    <p class="text-gray-600">Categories and dishes will appear here once they're added.</p>
                </div>
            @endif
        </div>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-4 mt-16">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                @if ($settings['show_social_links'] ?? true)
                    <p class="text-gray-400 mb-2">&copy; {{ date('Y') }}
                        {{ $user->restaurant_name ?? 'Restaurant' }}.
                        All
                        rights reserved.</p>
                @endif
                <p class="text-gray-500 text-xs">
                    Made by Dany Seifeddine. Want free menu? Contact: 03004699
                </p>
            </div>
        </footer>

    </div><!-- End Alpine x-data wrapper -->

    @include('public.menu.partials.scripts')
</body>

</html>
