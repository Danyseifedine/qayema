{{-- Decomposed Dish Layout - Similar to compact but each item in its own card --}}
@php
    $pricePosition = $pricePosition ?? 'bottom_right';

    // Calculate price
    $formattedPrice = null;
    $currency = 'USD';
    if (($settings['show_prices'] ?? true) && $dish->price) {
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
    }
@endphp

<div class="bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all duration-200 mb-3 last:mb-0">
    <div class="flex flex-col sm:flex-row items-start gap-3 sm:gap-4">
        <!-- Circular Image -->
        @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
            <div class="flex-shrink-0">
                <img src="{{ $dish->getFirstMediaUrl('images') }}" alt="{{ $dish->name }}"
                    class="w-14 h-14 sm:w-16 sm:h-16 rounded-full object-cover">
            </div>
        @elseif ($settings['show_dish_image'] ?? true)
            <div class="flex-shrink-0 w-14 h-14 sm:w-16 sm:h-16 rounded-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                <span class="text-white text-lg sm:text-xl font-bold">{{ strtoupper(substr($dish->name, 0, 1)) }}</span>
            </div>
        @endif

        <!-- Content -->
        <div class="flex-1 min-w-0 w-full sm:w-auto">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-2 sm:gap-4">
                <div class="flex-1 min-w-0">
                    <h3 class="text-sm sm:text-base font-bold text-gray-900 mb-1 break-words">{{ $dish->name }}</h3>
                    @if (($settings['show_dish_description'] ?? true) && $dish->description)
                        <p class="text-xs sm:text-sm text-gray-600 mb-1 line-clamp-2 break-words">{{ $dish->description }}</p>
                    @endif
                    @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                        <p class="text-xs text-gray-500 italic break-words">{{ $dish->ingredients }}</p>
                    @endif
                </div>
                @if (($settings['show_prices'] ?? true) && $dish->price)
                    <div class="flex-shrink-0 self-start sm:self-auto">
                        <span class="text-sm sm:text-base font-bold text-red-600 whitespace-nowrap">
                            @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                {{ $formattedPrice }} {{ $currency }}
                            @else
                                ${{ $formattedPrice }}
                            @endif
                        </span>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

