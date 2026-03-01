<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    @php
        $menuImage = $user->hasMedia('cover_image')
            ? $user->getFirstMediaUrl('cover_image')
            : ($user->hasMedia('logo')
                ? $user->getFirstMediaUrl('logo')
                : asset('images/logo/logo.png'));

        $menuDescription =
            $menu->description ?:
            "View the digital menu for {$user->restaurant_name}. Browse our delicious dishes and place your order.";
    @endphp

    <x-seo :title="$menu->name . ' - ' . ($user->restaurant_name ?? 'Menu')" :description="$menuDescription" :keywords="'menu, restaurant menu, digital menu, ' . ($user->restaurant_name ?? '') . ', ' . ($menu->name ?? '')" :url="route('public.menu', $menu->slug)" :image="$menuImage" :imageAlt="$menu->name . ' - ' . ($user->restaurant_name ?? 'Menu')"
        type="website" :siteName="config('seo.organization.name', 'Lebify Group')" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="antialiased bg-gray-50">
    <!-- Cover Image Section -->
    @if ($user->hasMedia('cover_image'))
        <div class="relative h-64 md:h-96 overflow-hidden">
            <img src="{{ $user->getFirstMediaUrl('cover_image') }}" alt="Cover Image" class="w-full h-full object-cover">
            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
        </div>
    @else
        <div class="relative h-64 md:h-96 bg-gradient-to-br from-indigo-600 to-purple-600 overflow-hidden">
            <div class="absolute inset-0 flex items-center justify-center">
                <div class="text-center text-white">
                    <svg class="w-24 h-24 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                        </path>
                    </svg>
                    <p class="text-xl font-semibold">{{ $user->restaurant_name ?? 'Restaurant' }}</p>
                </div>
            </div>
        </div>
    @endif

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16 md:-mt-24 relative z-10">
        <!-- Logo and Restaurant Info Card -->
        <div class="bg-white rounded-lg shadow-xl overflow-hidden">
            <div class="p-6 md:p-8">
                <div class="flex flex-col md:flex-row items-center md:items-start gap-6">
                    <!-- Logo -->
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

                    <!-- Restaurant Info -->
                    <div class="flex-1 text-center md:text-start">
                        <h1 class="text-3xl md:text-4xl font-bold text-gray-900 mb-2">
                            {{ $user->restaurant_name ?? 'Restaurant' }}
                        </h1>
                        @if ($menu->description)
                            <p class="text-gray-600 mb-4">{{ $menu->description }}</p>
                        @endif

                        <div class="flex flex-wrap gap-4 justify-center md:justify-start text-sm text-gray-600">
                            @if ($user->phone)
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                        </path>
                                    </svg>
                                    <span>{{ $user->phone }}</span>
                                </div>
                            @endif
                            @if ($user->address)
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Menu Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        @if ($categories->isNotEmpty())
            @foreach ($categories as $category)
                <div class="mb-12" id="category-{{ $category->id }}">
                    <!-- Category Header -->
                    <div class="mb-6">
                        <div class="flex items-center gap-4 mb-4">
                            @if ($category->hasMedia('image'))
                                <img src="{{ $category->getFirstMediaUrl('image') }}" alt="{{ $category->name }}"
                                    class="w-16 h-16 rounded-lg object-cover shadow-md">
                            @else
                                <div
                                    class="w-16 h-16 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center shadow-md">
                                    <span class="text-white text-xl font-bold">
                                        {{ strtoupper(substr($category->name, 0, 1)) }}
                                    </span>
                                </div>
                            @endif
                            <div>
                                <h2 class="text-3xl font-bold text-gray-900">{{ $category->name }}</h2>
                                @if ($category->description)
                                    <p class="text-gray-600 mt-1">{{ $category->description }}</p>
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
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach ($dishes as $dish)
                                <div
                                    class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                                    <!-- Dish Image -->
                                    @if ($dish->hasMedia('images'))
                                        <div class="relative h-48 overflow-hidden">
                                            <img src="{{ $dish->getFirstMediaUrl('images') }}"
                                                alt="{{ $dish->name }}" class="w-full h-full object-cover">
                                        </div>
                                    @else
                                        <div
                                            class="relative h-48 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                            <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                </path>
                                            </svg>
                                        </div>
                                    @endif

                                    <!-- Dish Info -->
                                    <div class="p-6">
                                        <div class="flex items-start justify-between mb-2">
                                            <h3 class="text-xl font-bold text-gray-900">{{ $dish->name }}</h3>
                                            @if ($dish->price)
                                                <span
                                                    class="text-2xl font-bold text-indigo-600">${{ number_format($dish->price, 2) }}</span>
                                            @endif
                                        </div>

                                        <!-- Dish Details -->
                                        <div class="space-y-2 text-sm text-gray-500">
                                            @if ($dish->ingredients)
                                                <div class="flex items-start gap-2">
                                                    <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none"
                                                        stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2">
                                                        </path>
                                                    </svg>
                                                    <span class="line-clamp-2">{{ $dish->ingredients }}</span>
                                                </div>
                                            @endif

                                        </div>
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

            <!-- Uncategorized Dishes -->
            @if ($uncategorizedDishes->isNotEmpty())
                <div class="mb-12" id="uncategorized">
                    <div class="mb-6">
                        <h2 class="text-3xl font-bold text-gray-900">Other Items</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($uncategorizedDishes as $dish)
                            <div
                                class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-xl transition-shadow duration-300">
                                @if ($dish->hasMedia('images'))
                                    <div class="relative h-48 overflow-hidden">
                                        <img src="{{ $dish->getFirstMediaUrl('images') }}" alt="{{ $dish->name }}"
                                            class="w-full h-full object-cover">
                                    </div>
                                @else
                                    <div
                                        class="relative h-48 bg-gradient-to-br from-gray-200 to-gray-300 flex items-center justify-center">
                                        <svg class="w-16 h-16 text-gray-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                            </path>
                                        </svg>
                                    </div>
                                @endif
                                <div class="p-6">
                                    <div class="flex items-start justify-between mb-2">
                                        <h3 class="text-xl font-bold text-gray-900">{{ $dish->name }}</h3>
                                        @if ($dish->price)
                                            <span
                                                class="text-2xl font-bold text-indigo-600">${{ number_format($dish->price, 2) }}</span>
                                        @endif
                                    </div>
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
    <footer class="bg-gray-900 text-white py-8 mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-gray-400">&copy; {{ date('Y') }} {{ $user->restaurant_name ?? 'Restaurant' }}. All
                rights reserved.</p>
        </div>
    </footer>

    <script>
        // Track time spent on page
        let startTime = Date.now();
        let timeSpent = 0;
        let lastUpdateTime = startTime;

        // Track time when page becomes hidden (user switches tab or closes)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden, calculate time spent so far
                timeSpent = Math.floor((Date.now() - startTime) / 1000);
                trackExit();
            } else {
                // Page is visible again, continue tracking from where we left off
                // Don't reset startTime, just update lastUpdateTime
                lastUpdateTime = Date.now();
            }
        });

        // Track time when user leaves the page
        window.addEventListener('beforeunload', function() {
            timeSpent = Math.floor((Date.now() - startTime) / 1000);
            trackExit();
        });

        // Also send periodic updates (every 30 seconds) to track active time
        setInterval(function() {
            if (!document.hidden) {
                timeSpent = Math.floor((Date.now() - startTime) / 1000);
                // Send heartbeat to update time spent
                updateTimeSpent(timeSpent);
            }
        }, 30000); // Every 30 seconds

        function updateTimeSpent(seconds) {
            if (seconds > 0) {
                const formData = new FormData();
                formData.append('time_spent', seconds);

                fetch('{{ route('public.menu.track-exit', $menu->slug) }}', {
                    method: 'POST',
                    body: formData,
                    keepalive: true
                }).catch(() => {
                    // Ignore errors
                });
            }
        }

        function trackExit() {
            if (timeSpent > 0) {
                // Use URL-encoded string for sendBeacon
                const data = 'time_spent=' + encodeURIComponent(timeSpent);
                const blob = new Blob([data], {
                    type: 'application/x-www-form-urlencoded'
                });
                navigator.sendBeacon('{{ route('public.menu.track-exit', $menu->slug) }}', blob);
            }
        }
    </script>
</body>

</html>
