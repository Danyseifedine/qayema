<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-seo title="MenuX - Create Beautiful Digital Menus"
        description="Create beautiful digital menus for your restaurant. Free up to 20 menu items. Share your menu with a simple link. Mobile optimized and easy to manage."
        keywords="digital menu, restaurant menu, online menu, menu creator, food menu, restaurant menu online, digital menu maker, menu sharing, restaurant technology"
        :url="url('/')" :image="asset('images/logo/logo.png')" imageAlt="MenuX - Create Beautiful Digital Menus" type="website"
        :siteName="config('app.name', 'MenuX')" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="antialiased bg-white text-gray-900">
    <!-- Navigation -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/logo/logo.png') }}" alt="MenuX" class="h-16 w-auto">
                </div>
                <div class="flex items-center gap-4">
                    @auth
                        <a href="{{ route('dashboard') }}"
                            class="text-gray-700 hover:text-indigo-600 font-medium transition-colors">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                            class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-indigo-700 transition-colors">
                            Sign In
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="py-20 sm:py-32">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-gray-900 mb-6">
                Create Beautiful Digital Menus
            </h1>
            <p class="text-xl text-gray-600 mb-8 max-w-2xl mx-auto">
                Transform your restaurant's menu into a stunning digital experience.
                Simple, fast, and shareable.
            </p>
            @auth
                <a href="{{ route('dashboard') }}"
                    class="inline-block bg-indigo-600 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-indigo-700 transition-colors shadow-lg">
                    Go to Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="inline-block bg-indigo-600 text-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-indigo-700 transition-colors shadow-lg">
                    Get Started
                </a>
            @endauth
        </div>
    </section>

    <!-- Pricing Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Simple Pricing</h2>
                <p class="text-lg text-gray-600">Start free, upgrade when you need more</p>
            </div>

            <div class="bg-white rounded-2xl shadow-lg p-8 border-2 border-indigo-100">
                <div class="text-center mb-8">
                    <div
                        class="inline-block bg-green-100 text-green-800 px-4 py-2 rounded-full text-sm font-semibold mb-4">
                        Free Plan Available
                    </div>
                    <h3 class="text-2xl font-bold text-gray-900 mb-2">Free Menu</h3>
                    <p class="text-gray-600 mb-6">Perfect for getting started</p>
                </div>

                <div class="space-y-6 mb-8">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">Up to 20 menu items</p>
                            <p class="text-gray-600 text-sm">Add up to 20 dishes to your menu for free</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">Beautiful design</p>
                            <p class="text-gray-600 text-sm">Professional-looking digital menu</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">Share instantly</p>
                            <p class="text-gray-600 text-sm">One link to share with your customers</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-green-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">Mobile optimized</p>
                            <p class="text-gray-600 text-sm">Works perfectly on all devices</p>
                        </div>
                    </div>
                </div>

                <div class="bg-indigo-50 rounded-lg p-6 mb-6">
                    <p class="text-gray-900 font-semibold mb-2">Want to start with the free menu?</p>
                    <p class="text-gray-700 mb-4">Contact us to get started:</p>
                    <a href="tel:+96103004699"
                        class="inline-flex items-center gap-2 text-indigo-600 font-semibold hover:text-indigo-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                            </path>
                        </svg>
                        +961 03004699
                    </a>
                </div>

                <div class="border-t border-gray-200 pt-6">
                    <p class="text-gray-600 text-sm mb-2">Need more than 20 items?</p>
                    <p class="text-gray-900 font-semibold">Upgrade for a small price</p>
                    <p class="text-gray-600 text-sm mt-1">Contact us to discuss pricing for unlimited menu items</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Why Choose MenuX?</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Fast & Easy</h3>
                    <p class="text-gray-600">Create your menu in minutes, no technical skills needed</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Beautiful Design</h3>
                    <p class="text-gray-600">Professional-looking menus that impress your customers</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Share Instantly</h3>
                    <p class="text-gray-600">One simple link to share with all your customers</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-indigo-600">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Ready to Get Started?</h2>
            <p class="text-xl text-indigo-100 mb-8">Start with a free menu today</p>
            @auth
                <a href="{{ route('dashboard') }}"
                    class="inline-block bg-white text-indigo-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-colors shadow-lg">
                    Go to Dashboard
                </a>
            @else
                <a href="{{ route('login') }}"
                    class="inline-block bg-white text-indigo-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-colors shadow-lg">
                    Sign In
                </a>
            @endauth
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center gap-3 mb-4 md:mb-0">
                    <img src="{{ asset('images/logo/logo.png') }}" alt="MenuX" class="h-12 w-auto">
                </div>
                <p class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} MenuX. All rights reserved.
                </p>
            </div>
        </div>
    </footer>
</body>

</html>
