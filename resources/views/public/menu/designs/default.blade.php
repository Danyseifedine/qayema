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
    $categoryLayout = $settings['category_layout'] ?? 'grid';
    $categoryCollapsible = ($settings['category_collapsible'] ?? true) && $categoryLayout === 'grid';
    $defaultState = $settings['category_default_state'] ?? 'open';

    // Build category states for Alpine.js (only needed if collapsible and grid layout)
    $categoryStates = [];
    if ($categoryCollapsible) {
        foreach ($categories as $category) {
            $categoryStates[(string) $category->id] = $defaultState === 'open';
        }
    }

    // For tabs layout, get first category id as default active tab
    $firstCategoryId = $categories->first()?->id ?? null;
@endphp

<body class="antialiased bg-gray-50">
    <div x-data="{
        categoryStates: {{ json_encode($categoryStates) }},
        toggleCategory(id) { this.categoryStates[String(id)] = !this.categoryStates[String(id)]; },
        activeTab: '{{ $firstCategoryId }}'
    }">

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
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            @if ($categories->isNotEmpty())
                {{-- ============================================= --}}
                {{-- TABS LAYOUT --}}
                {{-- ============================================= --}}
                @if ($categoryLayout === 'tabs')
                    <div class="mt-8" x-data="{
                        scrollContainer: null,
                        showLeftArrow: false,
                        showRightArrow: true,
                        checkScroll() {
                            if (this.scrollContainer) {
                                this.showLeftArrow = this.scrollContainer.scrollLeft > 10;
                                this.showRightArrow = this.scrollContainer.scrollLeft < (this.scrollContainer.scrollWidth - this.scrollContainer.clientWidth - 10);
                            }
                        },
                        scrollLeft() {
                            this.scrollContainer.scrollBy({ left: -200, behavior: 'smooth' });
                        },
                        scrollRight() {
                            this.scrollContainer.scrollBy({ left: 200, behavior: 'smooth' });
                        }
                    }" x-init="scrollContainer = $refs.tabsContainer;
                    checkScroll()">
                        <!-- Sticky Category Tabs -->
                        <div
                            class="sticky top-0 z-40 bg-gray-50/95 backdrop-blur-sm py-4 -mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                            <div class="relative">
                                <!-- Left Scroll Indicator -->
                                <div x-show="showLeftArrow" x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    class="absolute left-0 top-0 bottom-0 z-10 flex items-center">
                                    <button @click="scrollLeft()"
                                        class="w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-indigo-600 hover:bg-white transition-all duration-200 -ml-1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 19l-7-7 7-7"></path>
                                        </svg>
                                    </button>
                                    <div
                                        class="w-8 h-full bg-gradient-to-r from-gray-50 to-transparent pointer-events-none">
                                    </div>
                                </div>

                                <!-- Tabs Container -->
                                <div class="overflow-x-auto scrollbar-hide" x-ref="tabsContainer"
                                    @scroll="checkScroll()">
                                    <div class="flex gap-2 min-w-max pb-1 px-1">
                                        @foreach ($categories as $category)
                                            <button @click="activeTab = '{{ $category->id }}'"
                                                :class="activeTab === '{{ $category->id }}'
                                                    ?
                                                    'bg-indigo-600 text-white shadow-lg shadow-indigo-500/30' :
                                                    'bg-white text-gray-700 hover:bg-gray-100 shadow'"
                                                class="px-5 py-2.5 rounded-full text-sm font-semibold whitespace-nowrap transition-all duration-200 flex items-center gap-2">
                                                {{ $category->name }}
                                                <span
                                                    :class="activeTab === '{{ $category->id }}'
                                                        ?
                                                        'bg-white/20 text-white' :
                                                        'bg-gray-100 text-gray-600'"
                                                    class="text-xs px-2 py-0.5 rounded-full font-medium">
                                                    {{ $category->dishes->where('is_available', true)->count() }}
                                                </span>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>

                                <!-- Right Scroll Indicator -->
                                <div x-show="showRightArrow" x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                                    class="absolute right-0 top-0 bottom-0 z-10 flex items-center">
                                    <div
                                        class="w-8 h-full bg-gradient-to-l from-gray-50 to-transparent pointer-events-none">
                                    </div>
                                    <button @click="scrollRight()"
                                        class="w-8 h-8 bg-white/90 backdrop-blur-sm rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-indigo-600 hover:bg-white transition-all duration-200 -mr-1">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Scroll hint text (shown only when scrollable) -->
                            <div x-show="showRightArrow" class="text-center mt-2">
                                <span class="text-xs text-gray-400 flex items-center justify-center gap-1">
                                    <svg class="w-3 h-3 animate-bounce-x" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                    </svg>
                                    Swipe to see more
                                </span>
                            </div>
                        </div>

                        <!-- Tab Content -->
                        <div class="mt-6">
                            @foreach ($categories as $category)
                                <div x-show="activeTab === '{{ $category->id }}'" x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 transform translate-y-2"
                                    x-transition:enter-end="opacity-100 transform translate-y-0">
                                    @php
                                        $dishes = $category->dishes->filter(fn($dish) => $dish->is_available === true);
                                    @endphp
                                    @if ($dishes->isNotEmpty())
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                            @foreach ($dishes as $dish)
                                                @include('public.menu.designs.partials.dish-card', [
                                                    'dish' => $dish,
                                                    'settings' => $settings,
                                                    'pricePosition' => $pricePosition,
                                                ])
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="text-center py-12">
                                            <p class="text-gray-500">No dishes available in this category.</p>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- ============================================= --}}
                    {{-- LIST LAYOUT --}}
                    {{-- ============================================= --}}
                @elseif ($categoryLayout === 'list')
                    <div class="mt-8 space-y-10">
                        @foreach ($categories as $category)
                            <div id="category-{{ $category->id }}">
                                <!-- Category Header -->
                                <div class="border-b-2 border-indigo-600 pb-3 mb-6">
                                    <h2 class="text-xl font-bold text-gray-900 uppercase tracking-wide">
                                        {{ $category->name }}</h2>
                                </div>

                                @php
                                    $dishes = $category->dishes->filter(fn($dish) => $dish->is_available === true);
                                @endphp

                                @if ($dishes->isNotEmpty())
                                    <div class="space-y-4">
                                        @foreach ($dishes as $dish)
                                            <div
                                                class="flex items-start justify-between gap-4 py-3 border-b border-gray-100 last:border-0">
                                                <div class="flex-1 min-w-0">
                                                    <h3 class="text-base font-semibold text-gray-900">
                                                        {{ $dish->name }}</h3>
                                                    @if (($settings['show_dish_description'] ?? true) && $dish->description)
                                                        <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                                            {{ $dish->description }}</p>
                                                    @endif
                                                </div>
                                                @if (($settings['show_prices'] ?? true) && $dish->price)
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
                                                    <div class="flex-shrink-0 text-right">
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
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 text-center py-4">No dishes available in this category.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- ============================================= --}}
                    {{-- HORIZONTAL CARDS LAYOUT --}}
                    {{-- ============================================= --}}
                @elseif ($categoryLayout === 'cards')
                    <div class="mt-8 space-y-12">
                        @foreach ($categories as $category)
                            <div id="category-{{ $category->id }}" class="relative">
                                <!-- Category Header -->
                                <div class="flex items-center justify-between mb-6">
                                    <div>
                                        <h2 class="text-2xl font-bold text-gray-900">{{ $category->name }}</h2>
                                        <p class="text-sm text-gray-500 mt-1">
                                            {{ $category->dishes->where('is_available', true)->count() }} items</p>
                                    </div>
                                    <div class="flex gap-2">
                                        <button
                                            onclick="document.getElementById('cards-{{ $category->id }}').scrollBy({left: -320, behavior: 'smooth'})"
                                            class="w-10 h-10 rounded-full bg-white shadow-lg flex items-center justify-center text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 19l-7-7 7-7"></path>
                                            </svg>
                                        </button>
                                        <button
                                            onclick="document.getElementById('cards-{{ $category->id }}').scrollBy({left: 320, behavior: 'smooth'})"
                                            class="w-10 h-10 rounded-full bg-white shadow-lg flex items-center justify-center text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 transition-all">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                @php
                                    $dishes = $category->dishes->filter(fn($dish) => $dish->is_available === true);
                                @endphp

                                @if ($dishes->isNotEmpty())
                                    <!-- Horizontal Scrolling Cards -->
                                    <div id="cards-{{ $category->id }}"
                                        class="flex gap-6 overflow-x-auto scrollbar-hide pb-4 -mx-4 px-4 snap-x snap-mandatory">
                                        @foreach ($dishes as $dish)
                                            <div class="flex-shrink-0 w-72 snap-start">
                                                <div
                                                    class="bg-white rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                                                    @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                                        <div class="relative h-44 overflow-hidden">
                                                            <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                                alt="{{ $dish->name }}"
                                                                class="w-full h-full object-cover">
                                                            @if (($settings['show_prices'] ?? true) && $dish->price)
                                                                @php
                                                                    $price = $dish->price;
                                                                    if (
                                                                        ($settings['currency_enabled'] ?? false) &&
                                                                        ($settings['exchange_rate'] ?? null)
                                                                    ) {
                                                                        $exchangeRate =
                                                                            (float) $settings['exchange_rate'];
                                                                        $price = $price * $exchangeRate;
                                                                        $currency =
                                                                            $settings['exchange_currency'] ?? 'USD';
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
                                                                    class="absolute top-3 right-3 bg-indigo-600 text-white px-3 py-1 rounded-full text-sm font-bold shadow-lg">
                                                                    @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                                        {{ $formattedPrice }} {{ $currency }}
                                                                    @else
                                                                        ${{ $formattedPrice }}
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @elseif ($settings['show_dish_image'] ?? true)
                                                        <div
                                                            class="relative h-44 bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                                            <span
                                                                class="text-white text-4xl font-bold opacity-50">{{ strtoupper(substr($dish->name, 0, 1)) }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="p-5">
                                                        <h3 class="font-bold text-gray-900 text-lg mb-2 line-clamp-1">
                                                            {{ $dish->name }}</h3>
                                                        @if (($settings['show_dish_description'] ?? true) && $dish->description)
                                                            <p class="text-gray-600 text-sm line-clamp-2">
                                                                {{ $dish->description }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-gray-500 text-center py-8">No dishes available in this category.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- ============================================= --}}
                    {{-- ACCORDION LAYOUT --}}
                    {{-- ============================================= --}}
                @elseif ($categoryLayout === 'accordion')
                    <div class="mt-8 space-y-4" x-data="{ openAccordion: '{{ $firstCategoryId }}' }">
                        @foreach ($categories as $category)
                            <div id="category-{{ $category->id }}"
                                class="bg-white rounded-xl shadow-md overflow-hidden">
                                <!-- Accordion Header -->
                                <button
                                    @click="openAccordion = openAccordion === '{{ $category->id }}' ? null : '{{ $category->id }}'"
                                    class="w-full px-6 py-5 flex items-center justify-between text-left hover:bg-gray-50 transition-colors">
                                    <div class="flex items-center gap-4">
                                        <div
                                            class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center shadow-md">
                                            <span
                                                class="text-white text-lg font-bold">{{ strtoupper(substr($category->name, 0, 1)) }}</span>
                                        </div>
                                        <div>
                                            <h2 class="text-lg font-bold text-gray-900">{{ $category->name }}</h2>
                                            <p class="text-sm text-gray-500">
                                                {{ $category->dishes->where('is_available', true)->count() }} items
                                                available</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <span class="hidden sm:inline-block text-sm text-gray-400">Click to
                                            expand</span>
                                        <div class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center transition-transform duration-300"
                                            :class="{ 'rotate-180': openAccordion === '{{ $category->id }}' }">
                                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                    </div>
                                </button>

                                <!-- Accordion Content -->
                                <div x-show="openAccordion === '{{ $category->id }}'" x-cloak
                                    x-transition:enter="transition ease-out duration-300"
                                    x-transition:enter-start="opacity-0 max-h-0"
                                    x-transition:enter-end="opacity-100 max-h-[2000px]"
                                    x-transition:leave="transition ease-in duration-200"
                                    x-transition:leave-start="opacity-100 max-h-[2000px]"
                                    x-transition:leave-end="opacity-0 max-h-0"
                                    class="border-t border-gray-100 overflow-hidden">
                                    @php
                                        $dishes = $category->dishes->filter(fn($dish) => $dish->is_available === true);
                                    @endphp

                                    @if ($dishes->isNotEmpty())
                                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                            @foreach ($dishes as $dish)
                                                <div
                                                    class="flex gap-4 p-4 rounded-xl bg-gray-50 hover:bg-indigo-50 transition-colors">
                                                    @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                                        <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                            alt="{{ $dish->name }}"
                                                            class="w-20 h-20 rounded-lg object-cover flex-shrink-0">
                                                    @elseif ($settings['show_dish_image'] ?? true)
                                                        <div
                                                            class="w-20 h-20 rounded-lg bg-gradient-to-br from-gray-300 to-gray-400 flex items-center justify-center flex-shrink-0">
                                                            <svg class="w-8 h-8 text-gray-500" fill="none"
                                                                stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    @endif
                                                    <div class="flex-1 min-w-0">
                                                        <div class="flex items-start justify-between gap-2">
                                                            <h3 class="font-semibold text-gray-900">
                                                                {{ $dish->name }}</h3>
                                                            @if (($settings['show_prices'] ?? true) && $dish->price)
                                                                @php
                                                                    $price = $dish->price;
                                                                    if (
                                                                        ($settings['currency_enabled'] ?? false) &&
                                                                        ($settings['exchange_rate'] ?? null)
                                                                    ) {
                                                                        $exchangeRate =
                                                                            (float) $settings['exchange_rate'];
                                                                        $price = $price * $exchangeRate;
                                                                        $currency =
                                                                            $settings['exchange_currency'] ?? 'USD';
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
                                                                    class="text-indigo-600 font-bold whitespace-nowrap">
                                                                    @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                                        {{ $formattedPrice }} {{ $currency }}
                                                                    @else
                                                                        ${{ $formattedPrice }}
                                                                    @endif
                                                                </span>
                                                            @endif
                                                        </div>
                                                        @if (($settings['show_dish_description'] ?? true) && $dish->description)
                                                            <p class="text-gray-600 text-sm mt-1 line-clamp-2">
                                                                {{ $dish->description }}</p>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="p-6 text-center">
                                            <p class="text-gray-500">No dishes available in this category.</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- ============================================= --}}
                    {{-- CAROUSEL LAYOUT --}}
                    {{-- ============================================= --}}
                @elseif ($categoryLayout === 'carousel')
                    <div class="mt-8">
                        @foreach ($categories as $index => $category)
                            @php
                                $dishes = $category->dishes->filter(fn($dish) => $dish->is_available === true);
                                $dishCount = $dishes->count();
                            @endphp
                            <div id="category-{{ $category->id }}" class="mb-16" x-data="{ currentSlide: 0, totalSlides: {{ $dishCount }} }">
                                <!-- Category Header -->
                                <div class="text-center mb-8">
                                    <h2 class="text-3xl font-bold text-gray-900 mb-2">{{ $category->name }}</h2>
                                    <div
                                        class="w-24 h-1 bg-gradient-to-r from-indigo-500 to-purple-600 mx-auto rounded-full">
                                    </div>
                                </div>

                                @if ($dishes->isNotEmpty())
                                    <!-- Carousel Container -->
                                    <div class="relative max-w-4xl mx-auto">
                                        <!-- Slides -->
                                        <div class="overflow-hidden rounded-2xl">
                                            <div class="flex transition-transform duration-500 ease-out"
                                                :style="'transform: translateX(-' + (currentSlide * 100) + '%)'">
                                                @foreach ($dishes as $dishIndex => $dish)
                                                    <div class="w-full flex-shrink-0">
                                                        <div
                                                            class="bg-white rounded-2xl shadow-2xl overflow-hidden mx-4">
                                                            <div class="md:flex">
                                                                @if ($settings['show_dish_image'] ?? true)
                                                                    <div class="md:w-1/2">
                                                                        @if ($dish->hasMedia('images'))
                                                                            <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                                                alt="{{ $dish->name }}"
                                                                                class="w-full h-64 md:h-full object-cover">
                                                                        @else
                                                                            <div
                                                                                class="w-full h-64 md:h-full bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center">
                                                                                <span
                                                                                    class="text-white text-6xl font-bold opacity-50">{{ strtoupper(substr($dish->name, 0, 1)) }}</span>
                                                                            </div>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                                <div class="p-8 md:w-1/2 flex flex-col justify-center">
                                                                    <h3 class="text-2xl font-bold text-gray-900 mb-4">
                                                                        {{ $dish->name }}</h3>
                                                                    @if (($settings['show_dish_description'] ?? true) && $dish->description)
                                                                        <p class="text-gray-600 mb-6">
                                                                            {{ $dish->description }}</p>
                                                                    @endif
                                                                    @if (($settings['show_ingredients'] ?? true) && $dish->ingredients)
                                                                        <p class="text-sm text-gray-500 mb-6">
                                                                            <span
                                                                                class="font-medium">Ingredients:</span>
                                                                            {{ $dish->ingredients }}
                                                                        </p>
                                                                    @endif
                                                                    @if (($settings['show_prices'] ?? true) && $dish->price)
                                                                        @php
                                                                            $price = $dish->price;
                                                                            if (
                                                                                ($settings['currency_enabled'] ??
                                                                                    false) &&
                                                                                ($settings['exchange_rate'] ?? null)
                                                                            ) {
                                                                                $exchangeRate =
                                                                                    (float) $settings['exchange_rate'];
                                                                                $price = $price * $exchangeRate;
                                                                                $currency =
                                                                                    $settings['exchange_currency'] ??
                                                                                    'USD';
                                                                                $formattedPrice = number_format(
                                                                                    $price,
                                                                                    0,
                                                                                    '.',
                                                                                    ',',
                                                                                );
                                                                            } else {
                                                                                $currency = 'USD';
                                                                                $formattedPrice = number_format(
                                                                                    $price,
                                                                                    2,
                                                                                );
                                                                            }
                                                                        @endphp
                                                                        <div
                                                                            class="text-3xl font-bold text-indigo-600">
                                                                            @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                                                {{ $formattedPrice }}
                                                                                {{ $currency }}
                                                                            @else
                                                                                ${{ $formattedPrice }}
                                                                            @endif
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>

                                        <!-- Navigation Arrows -->
                                        <button
                                            @click="currentSlide = currentSlide > 0 ? currentSlide - 1 : totalSlides - 1"
                                            class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 transition-all z-10">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 19l-7-7 7-7"></path>
                                            </svg>
                                        </button>
                                        <button
                                            @click="currentSlide = currentSlide < totalSlides - 1 ? currentSlide + 1 : 0"
                                            class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 w-12 h-12 bg-white rounded-full shadow-lg flex items-center justify-center text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 transition-all z-10">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>

                                        <!-- Dots Navigation -->
                                        <div class="flex justify-center gap-2 mt-6">
                                            @foreach ($dishes as $dotIndex => $dish)
                                                <button @click="currentSlide = {{ $dotIndex }}"
                                                    :class="currentSlide === {{ $dotIndex }} ? 'bg-indigo-600 w-8' :
                                                        'bg-gray-300 w-2'"
                                                    class="h-2 rounded-full transition-all duration-300"></button>
                                            @endforeach
                                        </div>
                                    </div>
                                @else
                                    <p class="text-gray-500 text-center py-8">No dishes available in this category.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    {{-- ============================================= --}}
                    {{-- TIMELINE LAYOUT --}}
                    {{-- ============================================= --}}
                @elseif ($categoryLayout === 'timeline')
                    <div class="mt-8 relative">
                        <!-- Timeline Line -->
                        <div
                            class="absolute left-8 md:left-1/2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-indigo-500 via-purple-500 to-pink-500">
                        </div>

                        @foreach ($categories as $index => $category)
                            @php
                                $dishes = $category->dishes->filter(fn($dish) => $dish->is_available === true);
                                $isEven = $index % 2 === 0;
                            @endphp
                            <div id="category-{{ $category->id }}" class="relative mb-16">
                                <!-- Timeline Node -->
                                <div
                                    class="absolute left-8 md:left-1/2 -translate-x-1/2 w-16 h-16 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center shadow-lg z-10">
                                    <span class="text-white text-2xl font-bold">{{ $index + 1 }}</span>
                                </div>

                                <!-- Content Card -->
                                <div
                                    class="ml-24 md:ml-0 md:w-[calc(50%-3rem)] {{ $isEven ? 'md:mr-auto md:pr-8' : 'md:ml-auto md:pl-8' }}">
                                    <div class="bg-white rounded-2xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                                        <!-- Category Header -->
                                        <h2 class="text-2xl font-bold text-gray-900 mb-2">{{ $category->name }}</h2>
                                        <p class="text-gray-500 text-sm mb-6">{{ $dishes->count() }} delicious items
                                        </p>

                                        @if ($dishes->isNotEmpty())
                                            <div class="space-y-4">
                                                @foreach ($dishes as $dish)
                                                    <div
                                                        class="flex gap-4 p-3 rounded-xl bg-gray-50 hover:bg-indigo-50 transition-colors">
                                                        @if (($settings['show_dish_image'] ?? true) && $dish->hasMedia('images'))
                                                            <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                                alt="{{ $dish->name }}"
                                                                class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                                        @endif
                                                        <div class="flex-1 min-w-0">
                                                            <div class="flex items-start justify-between gap-2">
                                                                <h3 class="font-semibold text-gray-900 text-sm">
                                                                    {{ $dish->name }}</h3>
                                                                @if (($settings['show_prices'] ?? true) && $dish->price)
                                                                    @php
                                                                        $price = $dish->price;
                                                                        if (
                                                                            ($settings['currency_enabled'] ?? false) &&
                                                                            ($settings['exchange_rate'] ?? null)
                                                                        ) {
                                                                            $exchangeRate =
                                                                                (float) $settings['exchange_rate'];
                                                                            $price = $price * $exchangeRate;
                                                                            $currency =
                                                                                $settings['exchange_currency'] ?? 'USD';
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
                                                                        class="text-indigo-600 font-bold text-sm whitespace-nowrap">
                                                                        @if (($settings['currency_enabled'] ?? false) && ($settings['exchange_rate'] ?? null))
                                                                            {{ $formattedPrice }} {{ $currency }}
                                                                        @else
                                                                            ${{ $formattedPrice }}
                                                                        @endif
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if (($settings['show_dish_description'] ?? true) && $dish->description)
                                                                <p class="text-gray-600 text-xs mt-1 line-clamp-1">
                                                                    {{ $dish->description }}</p>
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-gray-500 text-center py-4">No dishes available.</p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- ============================================= --}}
                    {{-- GRID LAYOUT (DEFAULT) --}}
                    {{-- ============================================= --}}
                @else
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
                                            @if (($settings['show_category_description'] ?? true) && $category->description)
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
                                                    class="w-5 h-5 text-gray-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M12 6v12m6-6H6"></path>
                                                </svg>
                                                <!-- Minus Icon (shown when open) -->
                                                <svg x-show="categoryStates['{{ $category->id }}'] === true" x-cloak
                                                    class="w-5 h-5 text-gray-600" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M18 12H6"></path>
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
                                                                        $exchangeRate =
                                                                            (float) $settings['exchange_rate'];
                                                                        $price = $price * $exchangeRate;
                                                                        $currency =
                                                                            $settings['exchange_currency'] ?? 'USD';
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
                                                                        $exchangeRate =
                                                                            (float) $settings['exchange_rate'];
                                                                        $price = $price * $exchangeRate;
                                                                        $currency =
                                                                            $settings['exchange_currency'] ?? 'USD';
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
                                                                            {{ $formattedPrice }}
                                                                            {{ $currency }}
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
                                                                        $exchangeRate =
                                                                            (float) $settings['exchange_rate'];
                                                                        $price = $price * $exchangeRate;
                                                                        $currency =
                                                                            $settings['exchange_currency'] ?? 'USD';
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
                @endif
                {{-- End of Layout Conditions --}}

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

    </div><!-- End Alpine x-data wrapper -->

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-4"
        style="width: 100vw; margin-left: calc(-50vw + 50%); margin-right: calc(-50vw + 50%);">
        <div class="text-center">
            @if ($settings['show_social_links'] ?? true)
                <p class="text-gray-400 mb-2">&copy; {{ date('Y') }}
                    {{ $user->restaurant_name ?? 'Restaurant' }}.
                    All
                    rights reserved.</p>
            @endif
            <p class="text-gray-500 px-2 text-xs">
                Made by Dany Seifeddine. Want free menu? Contact: 03004699
            </p>
        </div>
    </footer>

    <!-- Share Button (only for default design) -->
    @if (($settings['enable_share'] ?? true) && ($settings['menu_design'] ?? 'default') === 'default')
        @php
            $sharePosition = $settings['share_button_position'] ?? 'bottom_right';
            $sharePositionClasses = match ($sharePosition) {
                'bottom_left' => 'bottom-6 left-6 items-start',
                'bottom_right' => 'bottom-6 right-6 items-end',
                'top_left' => 'top-2 left-4 items-start',
                'top_right' => 'top-2 right-4 items-end',
                default => 'bottom-6 right-6 items-end',
            };
            $menuPosition = match ($sharePosition) {
                'bottom_left' => 'mb-3',
                'bottom_right' => 'mb-3',
                'top_left' => 'mt-3',
                'top_right' => 'mt-3',
                default => 'mb-3',
            };
            $isTopPosition = in_array($sharePosition, ['top_left', 'top_right']);
        @endphp
        <div x-data="{ open: false }"
            class="fixed {{ $sharePositionClasses }} z-50 flex flex-col {{ $isTopPosition ? 'flex-col-reverse' : '' }}">
            <!-- Backdrop -->
            <div x-show="open" x-cloak x-transition:enter="transition-opacity ease-out duration-200"
                x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                x-transition:leave="transition-opacity ease-in duration-150" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0" @click="open = false"
                class="fixed inset-0 bg-black/20 backdrop-blur-sm"></div>

            <!-- Share Menu -->
            <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform scale-90 translate-y-4 rotate-3"
                x-transition:enter-end="opacity-100 transform scale-100 translate-y-0 rotate-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 transform scale-100 translate-y-0 rotate-0"
                x-transition:leave-end="opacity-0 transform scale-90 translate-y-4 -rotate-3"
                @click.away="open = false"
                class="{{ $menuPosition }} bg-white rounded-2xl shadow-2xl border border-gray-200 overflow-hidden min-w-[200px] transform-gpu">
                <!-- Copy Link -->
                <button @click="copyMenuLink(); open = false"
                    class="w-full px-5 py-3.5 text-left hover:bg-indigo-50 transition-all duration-200 flex items-center gap-3 text-gray-700 group relative overflow-hidden">
                    <div class="relative z-10 flex items-center gap-3">
                        <div
                            class="w-9 h-9 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shadow-md group-hover:shadow-lg">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z">
                                </path>
                            </svg>
                        </div>
                        <div class="flex flex-col">
                            <span
                                class="text-sm font-semibold text-gray-800 group-hover:text-indigo-600 transition-colors">Copy
                                Link</span>
                            <span class="text-xs text-gray-500">Share this menu</span>
                        </div>
                    </div>
                </button>
                <!-- WhatsApp -->
                @if (($settings['show_phone_number'] ?? true) && $user->phone)
                    <a href="#" @click.prevent="contactOnWhatsApp(); open = false"
                        class="w-full px-5 py-3.5 text-left hover:bg-green-50 transition-all duration-200 flex items-center gap-3 text-gray-700 border-t border-gray-200 group relative overflow-hidden">
                        <div class="relative z-10 flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-lg bg-gradient-to-br from-green-500 to-emerald-600 flex items-center justify-center group-hover:scale-110 transition-transform duration-200 shadow-md group-hover:shadow-lg">
                                <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                                    <path
                                        d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z">
                                    </path>
                                </svg>
                            </div>
                            <div class="flex flex-col">
                                <span
                                    class="text-sm font-semibold text-gray-800 group-hover:text-green-600 transition-colors">Contact
                                    on WhatsApp</span>
                                <span class="text-xs text-gray-500">Message us directly</span>
                            </div>
                        </div>
                    </a>
                @endif
            </div>
            <!-- Share Button -->
            <button @click="open = !open" type="button"
                class="w-10 h-10 bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 hover:from-indigo-700 hover:via-purple-700 hover:to-pink-600 text-white rounded-full shadow-lg flex items-center justify-center transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:ring-offset-2 transform-gpu hover:scale-105 active:scale-95">
                <!-- Icon Container -->
                <div class="transform transition-transform duration-300" :class="{ 'rotate-90': open }">
                    <svg x-show="!open" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                        stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z">
                        </path>
                    </svg>
                    <svg x-show="open" x-cloak class="w-5 h-5" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </div>
            </button>
        </div>
    @endif

    @include('public.menu.partials.scripts')

    @if (($settings['enable_share'] ?? true) && ($settings['menu_design'] ?? 'default') === 'default')
        <script>
            function copyMenuLink() {
                const menuUrl = window.location.href;
                navigator.clipboard.writeText(menuUrl).then(function() {
                    // Show notification
                    const notification = document.createElement('div');
                    notification.className =
                        'fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50 flex items-center gap-2';
                    notification.innerHTML = `
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Link copied to clipboard!</span>
                    `;
                    document.body.appendChild(notification);
                    setTimeout(() => {
                        notification.style.opacity = '0';
                        notification.style.transition = 'opacity 0.3s';
                        setTimeout(() => notification.remove(), 300);
                    }, 2000);
                }).catch(function(err) {
                    console.error('Failed to copy: ', err);
                    alert('Failed to copy link. Please copy manually: ' + menuUrl);
                });
            }

            function contactOnWhatsApp() {
                @if ($user->phone)
                    const phone = '{{ preg_replace('/[^0-9]/', '', $user->phone) }}';
                    const whatsappUrl = `https://wa.me/${phone}`;
                    window.open(whatsappUrl, '_blank');
                @endif
            }
        </script>
    @endif
</body>

</html>
