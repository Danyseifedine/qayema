<!DOCTYPE html>
<html lang="{{ $restaurant->default_locale ?? 'ar' }}" dir="{{ ($settings['menu_direction'] ?? 'ltr') === 'rtl' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $restaurant->name }}</title>

    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        [x-cloak] { display: none !important; }

        .dish-card:hover { transform: translateY(-2px); }

        .scrollbar-hide::-webkit-scrollbar { display: none; }
        .scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen" x-cloak>

    {{-- ─── HEADER ────────────────────────────────────────────────────── --}}
    <header class="bg-white shadow-sm sticky top-0 z-30">
        @php $hasCover = $restaurant->hasMedia('cover_image'); @endphp

        @if ($hasCover && ($settings['show_cover_image'] ?? true))
            <div class="h-36 sm:h-48 w-full overflow-hidden relative">
                <img
                    src="{{ $restaurant->getFirstMediaUrl('cover_image') }}"
                    alt="{{ $restaurant->name }}"
                    class="w-full h-full object-cover"
                >
                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
            </div>
        @endif

        <div class="max-w-3xl mx-auto px-4 py-4 flex items-center gap-4">
            @if ($restaurant->hasMedia('logo'))
                <img
                    src="{{ $restaurant->getFirstMediaUrl('logo') }}"
                    alt="{{ $restaurant->name }} logo"
                    class="w-14 h-14 rounded-full object-cover ring-2 ring-white shadow flex-shrink-0"
                >
            @endif

            <div class="flex-1 min-w-0">
                <h1 class="text-xl font-bold text-gray-900 truncate">{{ $restaurant->name }}</h1>

                @if ($restaurant->description)
                    <p class="text-sm text-gray-500 mt-0.5 line-clamp-2">{{ $restaurant->description }}</p>
                @endif

                <div class="flex flex-wrap gap-3 mt-2">
                    @if (($settings['show_phone_number'] ?? true) && $restaurant->phone)
                        <a href="tel:{{ $restaurant->country_code }}{{ $restaurant->phone }}"
                           class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-gray-700">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.948V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                            </svg>
                            {{ $restaurant->country_code }}{{ $restaurant->phone }}
                        </a>
                    @endif

                    @if (($settings['show_address'] ?? true) && $restaurant->address)
                        <span class="inline-flex items-center gap-1 text-xs text-gray-500">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            {{ $restaurant->address }}
                        </span>
                    @endif
                </div>
            </div>

            @if (($settings['show_social_links'] ?? true) && $restaurant->socialLinks->isNotEmpty())
                <div class="flex items-center gap-2 flex-shrink-0">
                    @foreach ($restaurant->socialLinks as $link)
                        <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                           class="text-gray-400 hover:text-gray-600 transition-colors">
                            <span class="sr-only">{{ $link->platform }}</span>
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z"/>
                            </svg>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </header>

    {{-- ─── CATEGORY NAV ───────────────────────────────────────────────── --}}
    @php
        $hasCategories = $categories->isNotEmpty();
        $hasUncategorized = $uncategorizedDishes->isNotEmpty();
    @endphp

    @if ($hasCategories)
        <nav class="bg-white border-b border-gray-100 sticky top-0 z-20 overflow-x-auto scrollbar-hide">
            <div class="max-w-3xl mx-auto px-4">
                <div class="flex gap-1 py-2">
                    @if ($hasUncategorized)
                        <a href="#uncategorized"
                           class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors whitespace-nowrap">
                            Other
                        </a>
                    @endif
                    @foreach ($categories as $category)
                        <a href="#category-{{ $category->id }}"
                           class="flex-shrink-0 px-3 py-1.5 rounded-full text-sm font-medium text-gray-600 hover:bg-gray-100 transition-colors whitespace-nowrap">
                            {{ $category->name }}
                        </a>
                    @endforeach
                </div>
            </div>
        </nav>
    @endif

    {{-- ─── MENU CONTENT ───────────────────────────────────────────────── --}}
    <main class="max-w-3xl mx-auto px-4 py-6 space-y-10 pb-28">

        {{-- Uncategorized dishes --}}
        @if ($hasUncategorized)
            <section id="uncategorized">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Other Items</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach ($uncategorizedDishes as $dish)
                        <x-menu.dish-card :dish="$dish" :currency="$restaurant->currency ?? 'USD'" :settings="$settings" />
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Categorized dishes --}}
        @foreach ($categories as $category)
            @if ($category->dishes->isNotEmpty())
                <section id="category-{{ $category->id }}">
                    <div class="flex items-center gap-3 mb-4">
                        @if ($category->hasMedia('image'))
                            <img
                                src="{{ $category->getFirstMediaUrl('image') }}"
                                alt="{{ $category->name }}"
                                class="w-8 h-8 rounded-lg object-cover"
                            >
                        @endif
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">{{ $category->name }}</h2>
                            @if ($category->description)
                                <p class="text-sm text-gray-500">{{ $category->description }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($category->dishes as $dish)
                            <x-menu.dish-card :dish="$dish" :currency="$restaurant->currency ?? 'USD'" :settings="$settings" />
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        @if (!$hasCategories && !$hasUncategorized)
            <div class="text-center py-20 text-gray-400">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="font-medium">Menu coming soon</p>
            </div>
        @endif
    </main>

    {{-- ─── CART COMPONENT ─────────────────────────────────────────────── --}}
    <x-menu.cart
        :slug="$restaurant->slug"
        :phone="($restaurant->country_code ?? '') . ($restaurant->phone ?? '')"
        :restaurant-name="$restaurant->name"
        :currency="$restaurant->currency ?? 'USD'"
    />

    {{-- Exit time tracking --}}
    <script>
        (function () {
            const start = Date.now();
            const trackExit = function () {
                const spent = Math.round((Date.now() - start) / 1000);
                navigator.sendBeacon(
                    '{{ route('public.menu.track-exit', $restaurant->slug) }}',
                    new Blob(
                        [new URLSearchParams({ _token: '{{ csrf_token() }}', time_spent: spent }).toString()],
                        { type: 'application/x-www-form-urlencoded' }
                    )
                );
            };
            window.addEventListener('pagehide', trackExit);
            window.addEventListener('beforeunload', trackExit);
        })();
    </script>
</body>
</html>
