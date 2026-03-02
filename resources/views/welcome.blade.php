<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ea580c">
    <meta name="format-detection" content="telephone=yes, email=yes">

    <x-seo title="MenuX by Lebify - Create Beautiful Digital Menus"
        description="MenuX by Lebify Group: create beautiful digital menus for your restaurant. Free: 20 categories, up to 80 dishes. Share your menu with a simple link. Built by the Lebify team in Lebanon."
        keywords="MenuX, Lebify, Lebify Group, Lebify team, digital menu, restaurant menu, online menu, menu creator, food menu, Lebanon, Barja"
        author="Lebify Group"
        :url="url('/')" :image="asset('images/logo/logo.png')" imageAlt="MenuX by Lebify - Digital Menus" type="website"
        :siteName="config('seo.organization.name', 'Lebify Group')" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
</head>
<body class="antialiased bg-white text-slate-900 font-sans overflow-x-hidden" x-data="{ navOpen: false, navScrolled: false }" x-init="window.addEventListener('scroll', () => { navScrolled = window.scrollY > 20 })">
    {{-- Navigation --}}
    <header class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
        :class="navScrolled ? 'bg-white/80 backdrop-blur-xl border-b border-slate-200/60 shadow-sm' : 'bg-transparent'">
        <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center min-h-14 sm:min-h-16 lg:min-h-20 py-2 sm:py-3 lg:py-4">
                <a href="{{ url('/') }}#hero" class="flex items-center shrink-0">
                    <img src="{{ asset('images/logo/Light BG Lebify Logo.svg') }}" alt="MenuX by Lebify" class="h-10 sm:h-12 md:h-14 lg:h-16 w-auto">
                </a>
                <div class="hidden lg:flex items-center gap-8">
                    <a href="#how-it-works" class="text-sm font-medium text-slate-600 hover:text-orange-600 transition-colors">How it works</a>
                    <a href="#features" class="text-sm font-medium text-slate-600 hover:text-orange-600 transition-colors">Features</a>
                    <a href="#pricing" class="text-sm font-medium text-slate-600 hover:text-orange-600 transition-colors">Pricing</a>
                    <a href="#contact" class="text-sm font-medium text-slate-600 hover:text-orange-600 transition-colors">Contact</a>
                </div>
                <div class="flex items-center gap-2 sm:gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="hidden sm:inline text-sm font-medium text-slate-600 hover:text-orange-600 transition-colors">Dashboard</a>
                        <x-btn href="{{ route('dashboard') }}" variant="primary" size="sm">My Menu</x-btn>
                    @else
                        <a href="{{ route('login') }}" class="hidden sm:inline text-sm font-medium text-slate-600 hover:text-orange-600 transition-colors">Sign in</a>
                        <x-btn href="{{ route('register') }}" variant="primary" size="sm">Get started</x-btn>
                    @endauth
                    <button type="button" class="lg:hidden p-2 -mr-2 min-w-[44px] min-h-[44px] flex items-center justify-center -my-1" @click="navOpen = !navOpen" aria-label="Toggle menu">
                        <svg class="w-6 h-6 text-slate-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!navOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path x-show="navOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div x-show="navOpen" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="lg:hidden border-t border-slate-200">
                <div class="py-3 sm:py-4 flex flex-col gap-0">
                    <a href="#how-it-works" class="text-sm font-medium py-3 px-1 text-slate-600 hover:text-orange-600 transition-colors -mx-1 rounded-lg hover:bg-orange-50/50" @click="navOpen = false">How it works</a>
                    <a href="#features" class="text-sm font-medium py-3 px-1 text-slate-600 hover:text-orange-600 transition-colors -mx-1 rounded-lg hover:bg-orange-50/50" @click="navOpen = false">Features</a>
                    <a href="#pricing" class="text-sm font-medium py-3 px-1 text-slate-600 hover:text-orange-600 transition-colors -mx-1 rounded-lg hover:bg-orange-50/50" @click="navOpen = false">Pricing</a>
                    <a href="#contact" class="text-sm font-medium py-3 px-1 text-slate-600 hover:text-orange-600 transition-colors -mx-1 rounded-lg hover:bg-orange-50/50" @click="navOpen = false">Contact</a>
                </div>
            </div>
        </nav>
    </header>

    {{-- Hero --}}
    <section id="hero" class="relative pt-24 sm:pt-28 md:pt-32 lg:pt-36 xl:pt-40 pb-14 sm:pb-16 md:pb-20 lg:pb-24 xl:pb-28 bg-white overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-5 md:px-6 lg:px-8 w-full">
            <div class="grid lg:grid-cols-2 gap-6 sm:gap-8 md:gap-10 lg:gap-12 xl:gap-16 items-center lg:items-center">
                <div class="order-2 lg:order-1 min-w-0 text-center lg:text-left">
                    <p class="text-xs sm:text-sm font-medium text-orange-600 uppercase tracking-[0.2em] mb-2 sm:mb-3">Digital menu platform</p>
                    <h1 class="text-xl sm:text-2xl md:text-3xl lg:text-4xl xl:text-5xl font-bold text-slate-900 tracking-tight leading-[1.2] sm:leading-[1.15] mb-3 sm:mb-4 max-w-xl mx-auto lg:mx-0">
                        Menus that work.<br>
                        <span class="text-orange-500">For every restaurant.</span>
                    </h1>
                    <p class="text-sm sm:text-base md:text-lg text-slate-600 max-w-lg mx-auto lg:mx-0 leading-relaxed mb-5 sm:mb-6 md:mb-8">
                        Create, customize, and share professional digital menus in minutes. QR codes, analytics, RTL support—everything you need, free to start.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 justify-center lg:justify-start">
                        @auth
                            <x-btn href="{{ route('dashboard') }}" variant="primary" size="sm">Go to dashboard</x-btn>
                        @else
                            <x-btn href="{{ route('register') }}" variant="primary" size="sm">Create your menu</x-btn>
                            <x-btn href="{{ route('login') }}" variant="outline" size="sm">Sign in</x-btn>
                        @endauth
                    </div>
                </div>
                <div class="order-1 lg:order-2 flex justify-center lg:justify-end min-w-0">
                    <img src="{{ asset('images/menu-test.png') }}" alt="Digital menu" class="w-full max-w-[220px] sm:max-w-[260px] md:max-w-[300px] lg:max-w-[340px] xl:max-w-[380px] h-auto object-contain mx-auto lg:mx-0">
                </div>
            </div>
        </div>
    </section>

    {{-- Stats bar --}}
    <section class="relative -mt-4 sm:-mt-6 lg:-mt-8 z-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl sm:rounded-2xl border border-slate-200/80 p-4 sm:p-6 lg:p-8">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6 lg:gap-8 xl:gap-12">
                    <div>
                        <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-orange-600">20</p>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5 sm:mt-1">Categories included</p>
                    </div>
                    <div>
                        <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-orange-600">80</p>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5 sm:mt-1">Dishes on free plan</p>
                    </div>
                    <div>
                        <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-orange-600">QR</p>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5 sm:mt-1">Code & shareable link</p>
                    </div>
                    <div>
                        <p class="text-xl sm:text-2xl lg:text-3xl font-bold text-orange-600">RTL</p>
                        <p class="text-xs sm:text-sm text-slate-500 mt-0.5 sm:mt-1">Arabic support</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- How it works --}}
    <section id="how-it-works" class="pt-8 sm:pt-10 md:pt-12 lg:pt-14 xl:pt-16 pb-8 sm:pb-10 md:pb-12 lg:pb-14 xl:pb-16 bg-white scroll-mt-14 sm:scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10 sm:mb-12 lg:mb-16">
                <p class="text-xs sm:text-sm font-medium text-orange-600 uppercase tracking-[0.2em] mb-2 sm:mb-3">Process</p>
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight mb-3 sm:mb-4">How it works</h2>
                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto px-2 sm:px-0">From setup to sharing in four steps</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5 lg:gap-6 xl:gap-8">
                @foreach([
                    ['num' => '01', 'title' => 'Create your menu', 'desc' => 'Add categories and dishes with photos, prices, and descriptions. Organize everything your way.'],
                    ['num' => '02', 'title' => 'Customize design', 'desc' => 'Choose layouts, fonts, RTL for Arabic, price positions. Make it yours.'],
                    ['num' => '03', 'title' => 'Share with customers', 'desc' => 'Get a unique link and QR code. Print it, share on social, or display at your venue.'],
                    ['num' => '04', 'title' => 'Track performance', 'desc' => 'See visits, unique visitors, time spent. Understand how customers use your menu.'],
                ] as $step)
                <div class="group relative bg-white rounded-xl border border-slate-200/80 p-4 sm:p-5 lg:p-6 xl:p-8 hover:border-orange-200 hover:shadow-lg hover:shadow-orange-100/50 transition-all duration-300">
                    <span class="text-3xl sm:text-4xl font-bold text-orange-500/30 group-hover:text-orange-500/50 transition-colors">{{ $step['num'] }}</span>
                    <h3 class="text-base sm:text-lg font-semibold text-slate-900 mt-3 sm:mt-4 mb-1.5 sm:mb-2">{{ $step['title'] }}</h3>
                    <p class="text-slate-600 text-xs sm:text-sm leading-relaxed">{{ $step['desc'] }}</p>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Features --}}
    <section id="features" class="pt-8 sm:pt-10 md:pt-12 lg:pt-14 xl:pt-16 pb-8 sm:pb-10 md:pb-12 lg:pb-14 xl:pb-16 bg-white scroll-mt-14 sm:scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10 sm:mb-12 lg:mb-16">
                <p class="text-xs sm:text-sm font-medium text-orange-600 uppercase tracking-[0.2em] mb-2 sm:mb-3">Features</p>
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight mb-3 sm:mb-4">Everything you need</h2>
                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto px-2 sm:px-0">Professional tools to build and manage your digital menu</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-3 sm:gap-4">
                @foreach([
                    ['icon' => 'qr', 'title' => 'QR Code', 'desc' => 'Generate and download QR codes. Scan to open menu instantly.'],
                    ['icon' => 'chart', 'title' => 'Analytics', 'desc' => 'Visits, unique visitors, time spent, bounce rate.'],
                    ['icon' => 'rtl', 'title' => 'RTL & Arabic', 'desc' => 'Full RTL support. Arabic fonts. Bilingual dashboard.'],
                    ['icon' => 'layout', 'title' => 'Flexible layouts', 'desc' => 'Grid, tabs, list, cards. Collapsible categories.'],
                    ['icon' => 'font', 'title' => '30+ fonts', 'desc' => 'Google Fonts including Arabic. Perfect typography.'],
                    ['icon' => 'currency', 'title' => 'Dual currency', 'desc' => 'Two currencies with exchange rate. Perfect for tourists.'],
                    ['icon' => 'share', 'title' => 'Social links', 'desc' => 'Instagram, Facebook, WhatsApp. Let customers find you.'],
                    ['icon' => 'mobile', 'title' => 'Mobile-first', 'desc' => 'Optimized for phones and tablets. Fast, touch-friendly.'],
                    ['icon' => 'image', 'title' => 'Dish images', 'desc' => 'Upload photos. Auto-optimized for fast loading.'],
                    ['icon' => 'brand', 'title' => 'Restaurant branding', 'desc' => 'Logo, cover image, name, address on menu.'],
                    ['icon' => 'allergen', 'title' => 'Ingredients & allergens', 'desc' => 'Display ingredients. Add allergen info per dish.'],
                    ['icon' => 'seo', 'title' => 'SEO optimized', 'desc' => 'Meta tags, Open Graph. Your menu is discoverable.'],
                ] as $feature)
                <div class="flex gap-3 sm:gap-4 p-3 sm:p-4 rounded-lg sm:rounded-xl border border-slate-200/80 hover:border-orange-200 hover:bg-orange-50/50 transition-all duration-200 min-w-0">
                    <div class="shrink-0 w-9 h-9 sm:w-10 sm:h-10 rounded-lg bg-orange-100 flex items-center justify-center">
                        @if($feature['icon'] === 'qr')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        @elseif($feature['icon'] === 'chart')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        @elseif($feature['icon'] === 'rtl')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                        @elseif($feature['icon'] === 'layout')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                        @elseif($feature['icon'] === 'font')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12"/></svg>
                        @elseif($feature['icon'] === 'currency')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @elseif($feature['icon'] === 'share')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg>
                        @elseif($feature['icon'] === 'mobile')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        @elseif($feature['icon'] === 'image')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        @elseif($feature['icon'] === 'brand')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        @elseif($feature['icon'] === 'allergen')
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        @else
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        @endif
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold text-slate-900 text-xs sm:text-sm">{{ $feature['title'] }}</h3>
                        <p class="text-slate-500 text-xs mt-0.5 leading-relaxed">{{ $feature['desc'] }}</p>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Pricing --}}
    <section id="pricing" class="pt-8 sm:pt-10 md:pt-12 lg:pt-14 xl:pt-16 pb-8 sm:pb-10 md:pb-12 lg:pb-14 xl:pb-16 bg-white scroll-mt-14 sm:scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10 sm:mb-12 lg:mb-16">
                <p class="text-xs sm:text-sm font-medium text-orange-600 uppercase tracking-[0.2em] mb-2 sm:mb-3">Pricing</p>
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight mb-3 sm:mb-4">Plans that scale with you</h2>
                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto px-2 sm:px-0">Start free. Upgrade when you need more. No hidden fees.</p>
            </div>
            <div class="grid lg:grid-cols-2 gap-4 sm:gap-5 lg:gap-6 xl:gap-8">
                {{-- Free Plan --}}
                <div class="h-full flex flex-col rounded-xl border border-slate-200/80 p-4 sm:p-5 lg:p-6 xl:p-8 hover:border-orange-200 hover:shadow-lg hover:shadow-orange-100/50 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-100">Most popular</span>
                        <span class="text-xs font-medium text-slate-500">Free forever</span>
                    </div>
                    <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-0.5">Starter</h3>
                    <p class="text-slate-600 text-xs sm:text-sm mb-4 sm:mb-6">Perfect for small restaurants and cafés</p>
                    <div class="flex items-baseline gap-1 mb-4 sm:mb-6">
                        <span class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-900 tracking-tight">$0</span>
                        <span class="text-slate-500 text-sm font-medium">/month</span>
                    </div>
                    @auth
                        <x-btn href="{{ route('dashboard') }}" variant="primary" size="sm" class="w-full justify-center">Go to dashboard</x-btn>
                    @else
                        <x-btn href="{{ route('register') }}" variant="primary" size="sm" class="w-full justify-center">Get started free</x-btn>
                    @endauth
                    <div class="border-t border-slate-100 mt-4 sm:mt-6 pt-4 sm:pt-6">
                        <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-2 sm:mb-3">What's included</p>
                        <ul class="flex flex-col gap-2">
                            @foreach(['20 categories', 'Up to 80 dishes', 'QR code & shareable link', 'Analytics & statistics', 'All layouts & customization', 'RTL & Arabic support', 'Dual currency display', '30+ Google Fonts'] as $item)
                            <li class="flex items-center gap-2 text-slate-600">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-emerald-50">
                                    <svg class="h-2.5 w-2.5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-xs">{{ $item }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                {{-- Enterprise Plan --}}
                <div class="h-full flex flex-col rounded-xl border border-slate-200/80 p-4 sm:p-5 lg:p-6 xl:p-8 hover:border-orange-200 hover:shadow-lg hover:shadow-orange-100/50 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-50 text-orange-700 border border-orange-100">Premium</span>
                    </div>
                    <h3 class="text-base sm:text-lg font-semibold text-slate-900 mb-0.5">Enterprise</h3>
                    <p class="text-slate-600 text-xs sm:text-sm mb-4 sm:mb-6">For chains, hotels, and high-volume venues</p>
                    <div class="flex items-baseline gap-1 mb-4 sm:mb-6">
                        <span class="text-xl sm:text-2xl lg:text-3xl font-bold text-slate-900 tracking-tight">Custom</span>
                    </div>
                    <a href="tel:+96103004699" class="inline-flex w-full items-center justify-center rounded-lg bg-orange-500 px-4 py-2.5 text-sm font-medium text-white shadow-sm transition duration-150 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500">
                        Contact sales
                    </a>
                    <div class="border-t border-slate-100 mt-4 sm:mt-6 pt-4 sm:pt-6">
                        <p class="text-xs font-medium text-slate-400 uppercase tracking-wider mb-2 sm:mb-3">Everything in Starter, plus</p>
                        <ul class="flex flex-col gap-2">
                            @foreach(['Unlimited categories', 'Unlimited dishes', 'Priority support'] as $item)
                            <li class="flex items-center gap-2 text-slate-600">
                                <span class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full bg-orange-50">
                                    <svg class="h-2.5 w-2.5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                </span>
                                <span class="text-xs">{{ $item }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <p class="text-center text-xs sm:text-sm text-slate-500 mt-6 sm:mt-8 px-2">No credit card required · Get started in minutes</p>
        </div>
    </section>

    {{-- Also From Lebify --}}
    <section class="pt-8 sm:pt-10 md:pt-12 lg:pt-14 xl:pt-16 pb-8 sm:pb-10 md:pb-12 lg:pb-14 xl:pb-16 bg-white scroll-mt-14 sm:scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-10 sm:mb-12 lg:mb-16">
                <p class="text-xs sm:text-sm font-medium text-orange-600 uppercase tracking-[0.2em] mb-2 sm:mb-3">Also from Lebify</p>
                <h2 class="text-2xl sm:text-3xl md:text-4xl lg:text-5xl font-bold text-slate-900 tracking-tight mb-3 sm:mb-4">More tools to help you succeed</h2>
                <p class="text-base sm:text-lg text-slate-600 max-w-2xl mx-auto px-2 sm:px-0">Professional tools from the Lebify team</p>
            </div>
            <a href="#" class="group block">
                <div class="flex flex-col sm:flex-row gap-4 sm:gap-6 p-4 sm:p-5 lg:p-6 rounded-xl border border-slate-200/80 hover:border-orange-200 hover:bg-orange-50/50 transition-all duration-200">
                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-slate-900 text-sm sm:text-base">CV Maker</h3>
                        <p class="text-slate-600 text-xs sm:text-sm mt-1 leading-relaxed">Build a standout resume in minutes. Modern templates, ATS-friendly formatting, and one-click PDF export.</p>
                        <div class="mt-3 sm:mt-4 flex flex-wrap gap-2">
                            <span class="inline-flex items-center rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700">Modern templates</span>
                            <span class="inline-flex items-center rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700">PDF export</span>
                            <span class="inline-flex items-center rounded-full bg-orange-50 px-3 py-1 text-xs font-medium text-orange-700">ATS-friendly</span>
                        </div>
                    </div>
                    <div class="shrink-0 flex items-center sm:justify-end">
                        <span class="inline-flex items-center gap-2 text-xs sm:text-sm font-medium text-orange-600 group-hover:text-orange-700 transition-colors">
                            Visit
                            <svg class="h-5 w-5 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                        </span>
                    </div>
                </div>
            </a>
        </div>
    </section>

    {{-- Footer --}}
    <footer id="contact" class="bg-white border-t border-slate-200 scroll-mt-14 sm:scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-14 lg:py-16">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8 sm:gap-10 lg:gap-8">
                <div class="sm:col-span-2 lg:col-span-1">
                    <a href="{{ url('/') }}#hero" class="flex items-center mb-3 sm:mb-4">
                        <img src="{{ asset('images/logo/Light BG Lebify Logo.svg') }}" alt="MenuX by Lebify" class="h-20 sm:h-24 lg:h-28 w-auto">
                    </a>
                    <p class="text-slate-600 text-xs sm:text-sm leading-relaxed">Digital menus for your restaurant by Lebify Group. Free to start, easy to use.</p>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-900 mb-3 sm:mb-4 text-xs sm:text-sm uppercase tracking-wider">Quick links</h4>
                    <ul class="flex flex-col gap-2 sm:gap-3">
                        <li><a href="#how-it-works" class="text-slate-600 hover:text-orange-600 transition-colors text-sm">How it works</a></li>
                        <li><a href="#features" class="text-slate-600 hover:text-orange-600 transition-colors text-sm">Features</a></li>
                        <li><a href="#pricing" class="text-slate-600 hover:text-orange-600 transition-colors text-sm">Pricing</a></li>
                        @guest
                        <li><a href="{{ route('login') }}" class="text-slate-600 hover:text-orange-600 transition-colors text-sm">Sign in</a></li>
                        <li><a href="{{ route('register') }}" class="text-slate-600 hover:text-orange-600 transition-colors text-sm">Register</a></li>
                        @endguest
                        <li><a href="https://lebify.dev" target="_blank" rel="noopener noreferrer" class="text-slate-600 hover:text-orange-600 transition-colors text-sm">Lebify</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-900 mb-3 sm:mb-4 text-xs sm:text-sm uppercase tracking-wider">Contact</h4>
                    <ul class="flex flex-col gap-3 sm:gap-4">
                        <li>
                            <a href="tel:+96103004699" class="flex items-center gap-3 text-slate-600 hover:text-orange-600 transition-colors group">
                                <span class="w-9 h-9 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center group-hover:bg-orange-100 group-hover:border-orange-200 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </span>
                                <span>+961 03 004 699</span>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:dany.a.seifeddine@gmail.com" class="flex items-center gap-3 text-slate-600 hover:text-orange-600 transition-colors group">
                                <span class="w-9 h-9 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center group-hover:bg-orange-100 group-hover:border-orange-200 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </span>
                                <span class="break-all">dany.a.seifeddine@gmail.com</span>
                            </a>
                        </li>
                        <li>
                            <div class="flex items-start gap-3 text-slate-600">
                                <span class="w-9 h-9 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <span>Barja, Lebanon</span>
                            </div>
                        </li>
                        <li>
                            <a href="https://www.instagram.com/lebify_team/" target="_blank" rel="noopener noreferrer" class="flex items-center gap-3 text-slate-600 hover:text-orange-600 transition-colors group">
                                <span class="w-9 h-9 rounded-lg bg-slate-50 border border-slate-200 flex items-center justify-center group-hover:bg-orange-100 group-hover:border-orange-200 transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>
                                </span>
                                <span>@lebify_team</span>
                            </a>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-slate-900 mb-3 sm:mb-4 text-xs sm:text-sm uppercase tracking-wider">Get started</h4>
                    <p class="text-slate-600 text-xs sm:text-sm mb-3 sm:mb-4">Create your free menu in minutes.</p>
                    @guest
                    <x-btn href="{{ route('register') }}" variant="primary" size="sm">Get started free</x-btn>
                    @else
                    <x-btn href="{{ route('dashboard') }}" variant="primary" size="sm">Dashboard</x-btn>
                    @endguest
                </div>
            </div>
            <div class="border-t border-slate-200 mt-8 sm:mt-10 lg:mt-12 pt-6 sm:pt-8 flex flex-col sm:flex-row justify-between items-center gap-3 sm:gap-4 text-center sm:text-left">
                <p class="text-slate-500 text-xs sm:text-sm">&copy; {{ date('Y') }} MenuX by <a href="https://lebify.dev" target="_blank" rel="noopener noreferrer" class="text-orange-600 hover:text-orange-700 transition-colors font-medium">Lebify Team</a>. All rights reserved.</p>
                <p class="text-slate-500 text-xs sm:text-sm">Lebify · Barja, Lebanon</p>
            </div>
        </div>
    </footer>

    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            });
        });
    </script>
</body>
</html>
