{{-- Dish Card Component for Menu Layouts --}}
@php
    $pricePosition = $pricePosition ?? 'bottom_right';
@endphp

<div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
    <!-- Dish Image -->
    @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
        <div class="relative h-48 overflow-hidden">
            <img src="{{ $dish->getFirstMediaUrl('images') }}" alt="{{ $dish->name }}"
                class="w-full h-full object-cover">
            @if (($settings['show_prices'] ?? true) && $dish->price && $pricePosition !== 'next_to_title')
                @php
                    $price = $dish->price;
                    if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null)) {
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
        <div class="relative h-48 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                </path>
            </svg>
            @if (($settings['show_prices'] ?? true) && $dish->price && $pricePosition !== 'next_to_title')
                @php
                    $price = $dish->price;
                    if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null)) {
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

    <!-- Dish Info -->
    <div class="p-6">
        <div class="flex items-start justify-between mb-2 gap-4">
            <h3 class="text-lg font-bold text-gray-900 flex-1 min-w-0 pr-4">
                {{ $dish->name }}</h3>
            @if (($settings['show_prices'] ?? true) && $dish->price && ($pricePosition ?? 'bottom_right') === 'next_to_title')
                @php
                    $price = $dish->price;
                    if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null)) {
                        $exchangeRate = (float) $settings['exchange_rate'];
                        $price = $price * $exchangeRate;
                        $currency = $settings['exchange_currency'] ?? 'USD';
                        $formattedPrice = number_format($price, 0, '.', ',');
                    } else {
                        $currency = 'USD';
                        $formattedPrice = number_format($price, 2);
                    }
                @endphp
                <span class="text-sm font-bold text-indigo-600 whitespace-nowrap flex-shrink-0">
                    @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                        {{ $formattedPrice }} {{ $currency }}
                    @else
                        ${{ $formattedPrice }}
                    @endif
                </span>
            @endif
        </div>

        <!-- Dish Description -->
        @if (($settings['show_dish_description'] ?? true) && $dish->description)
            <p class="text-gray-600 text-sm line-clamp-2">{{ $dish->description }}</p>
        @endif

        <!-- Dish Details -->
        @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
            <div class="text-xs text-gray-500 mt-2">
                <span>{{ $dish->ingredients }}</span>
            </div>
        @endif
    </div>
</div>

