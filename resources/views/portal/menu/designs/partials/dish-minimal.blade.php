{{-- Minimal Dish Layout - Clean, simple design --}}
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

<div class="bg-white rounded-lg border border-gray-200 p-4 hover:border-indigo-300 hover:shadow-md transition-all duration-200">
    <div class="flex items-start justify-between gap-4 mb-3">
        <div class="flex-1 min-w-0">
            <h3 class="text-lg font-semibold text-gray-900 mb-1">{{ $dish->name }}</h3>
            @if (($settings['show_dish_description'] ?? true) && $dish->description)
                <p class="text-sm text-gray-600 line-clamp-2">{{ $dish->description }}</p>
            @endif
        </div>
        @if (($settings['show_prices'] ?? true) && $dish->price)
            <div class="flex-shrink-0">
                <span class="text-lg font-bold text-indigo-600">
                    @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                        {{ $formattedPrice }} {{ $currency }}
                    @else
                        ${{ $formattedPrice }}
                    @endif
                </span>
            </div>
        @endif
    </div>

    @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
        <div class="mb-3 rounded-lg overflow-hidden">
            <img src="{{ $dish->getFirstMediaUrl('images') }}" alt="{{ $dish->name }}"
                class="w-full h-32 object-cover">
        </div>
    @endif

    @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
        <p class="text-xs text-gray-500 italic">{{ $dish->ingredients }}</p>
    @endif
</div>
