@props([
    'dish',
    'currency' => 'USD',
    'settings' => [],
])

@php
    $showPrice = $settings['show_prices'] ?? true;
    $showImage = $settings['show_dish_image'] ?? true;
    $showIngredients = $settings['show_ingredients'] ?? true;
    $hasImage = $showImage && $dish->hasMedia('image');

    $dishData = [
        'id'    => $dish->id,
        'name'  => $dish->name,
        'price' => (float) ($dish->price ?? 0),
        'image' => $hasImage ? $dish->getFirstMediaUrl('image') : null,
    ];
@endphp

<div
    class="dish-card bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden transition-transform duration-200 cursor-pointer"
    @click="Alpine.store('cart').add({{ Js::from($dishData) }})"
    role="button"
    tabindex="0"
    @keydown.enter="Alpine.store('cart').add({{ Js::from($dishData) }})"
    @keydown.space.prevent="Alpine.store('cart').add({{ Js::from($dishData) }})"
    title="Add {{ $dish->name }} to cart"
>
    @if ($hasImage)
        <div class="relative h-36 overflow-hidden">
            <img
                src="{{ $dish->getFirstMediaUrl('image') }}"
                alt="{{ $dish->name }}"
                class="w-full h-full object-cover"
                loading="lazy"
            >
            @if (!$dish->is_available)
                <div class="absolute inset-0 bg-black/50 flex items-center justify-center">
                    <span class="text-white text-xs font-semibold px-2 py-1 bg-black/60 rounded-full">Unavailable</span>
                </div>
            @endif
        </div>
    @endif

    <div class="p-4">
        <div class="flex items-start justify-between gap-2">
            <h3 class="text-sm font-semibold text-gray-900 leading-snug">{{ $dish->name }}</h3>

            @if ($showPrice && $dish->price !== null)
                <span class="text-sm font-bold text-green-600 flex-shrink-0">
                    {{ Number::currency((float) $dish->price, $currency) }}
                </span>
            @endif
        </div>

        @if ($showIngredients && $dish->ingredients)
            <p class="text-xs text-gray-400 mt-1 line-clamp-2">{{ $dish->ingredients }}</p>
        @endif

        @if ($dish->description)
            <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $dish->description }}</p>
        @endif

        <div class="mt-3 flex items-center justify-between">
            <span class="inline-flex items-center gap-1 text-xs text-green-600 font-medium">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Add to cart
            </span>
        </div>
    </div>
</div>
