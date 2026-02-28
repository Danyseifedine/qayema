<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#ea580c">
    <meta name="format-detection" content="telephone=yes, email=yes">

    <x-seo title="MenuX - Create Beautiful Digital Menus"
        description="Create beautiful digital menus for your restaurant. Free: 20 categories, up to 80 dishes. Share your menu with a simple link. Mobile optimized and easy to manage. Based in Lebanon."
        keywords="digital menu, restaurant menu, online menu, menu creator, food menu, restaurant menu online, digital menu maker, menu sharing, restaurant technology, Lebanon, Barja"
        author="MenuX - dany.a.seifeddine@gmail.com"
        :url="url('/')" :image="asset('images/logo/logo.png')" imageAlt="MenuX - Create Beautiful Digital Menus" type="website"
        :siteName="config('app.name', 'MenuX')" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

</head>
<body class="antialiased bg-slate-50 text-slate-900 font-sans" x-data="{ navScrolled: false }" x-init="window.addEventListener('scroll', () => { navScrolled = window.scrollY > 50 })">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300"
        :class="navScrolled ? 'bg-white/95 backdrop-blur-md shadow-sm border-b border-slate-200/80' : 'bg-transparent'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16 lg:h-18">
                <a href="{{ url('/') }}#hero" class="flex items-center gap-3">
                    <img src="{{ asset('images/logo/logo.png') }}" alt="MenuX" class="h-12 w-auto transition-all duration-300" :class="!navScrolled && 'brightness-0 invert opacity-95'">
                    <span class="font-bold text-lg hidden sm:inline" :class="navScrolled ? 'text-slate-800' : 'text-white'">MenuX</span>
                </a>
                <div class="flex items-center gap-4 sm:gap-6">
                    <a href="#how-it-works" class="hidden sm:inline text-sm font-medium transition-colors hover:text-orange-500" :class="navScrolled ? 'text-slate-600' : 'text-white/90 hover:text-white'">How it works</a>
                    <a href="#features" class="hidden sm:inline text-sm font-medium transition-colors hover:text-orange-500" :class="navScrolled ? 'text-slate-600' : 'text-white/90 hover:text-white'">Features</a>
                    <a href="#pricing" class="hidden sm:inline text-sm font-medium transition-colors hover:text-orange-500" :class="navScrolled ? 'text-slate-600' : 'text-white/90 hover:text-white'">Pricing</a>
                    <a href="#contact" class="hidden sm:inline text-sm font-medium transition-colors hover:text-orange-500" :class="navScrolled ? 'text-slate-600' : 'text-white/90 hover:text-white'">Contact</a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-sm font-medium transition-colors" :class="navScrolled ? 'text-slate-600 hover:text-orange-600' : 'text-white/90 hover:text-white'">Dashboard</a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 transition-colors shadow-sm">My Menu</a>
                    @else
                        <a href="{{ route('login') }}" class="text-sm font-medium transition-colors" :class="navScrolled ? 'text-slate-600 hover:text-orange-600' : 'text-white/90 hover:text-white'">Sign In</a>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 transition-colors shadow-sm">Get Started Free</a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="hero" class="relative bg-orange-500 pt-24 pb-20 sm:pt-32 sm:pb-28">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-4xl mx-auto">
                <p class="text-orange-100 text-sm font-medium mb-6">Free to start • No credit card required</p>
                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-bold text-white tracking-tight mb-6 leading-[1.1]">
                    Digital menus that wow your customers
                </h1>
                <p class="text-lg sm:text-xl text-white/90 mb-10 max-w-2xl mx-auto leading-relaxed">
                    Create, customize, and share beautiful restaurant menus in minutes. QR codes, analytics, RTL support, and more, all in one place.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-white text-orange-600 font-semibold text-lg hover:bg-orange-50 transition-colors">
                            Go to Dashboard
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-white text-orange-600 font-semibold text-lg hover:bg-orange-50 transition-colors">
                            Create Your Menu Free
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-white/20 text-white font-semibold text-lg border border-white/40 hover:bg-white/30 transition-colors">
                            Sign In
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section id="how-it-works" class="py-20 sm:py-28 bg-white overflow-hidden scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">How it works</h2>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">From setup to sharing in four simple steps</p>
            </div>
            <div class="relative">
                <div class="hidden lg:block absolute top-1/2 left-0 right-0 h-0.5 bg-gradient-to-r from-orange-200 via-orange-100 to-orange-200 -translate-y-1/2" style="margin-left: 12%; margin-right: 12%;"></div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 lg:gap-6">
                    <div class="relative bg-white rounded-2xl border-2 border-slate-100 p-6 lg:p-8 hover:border-orange-200 hover:shadow-lg transition-all group">
                        <div class="absolute -top-4 left-6 w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center font-bold text-lg shadow-lg group-hover:scale-110 transition-transform">1</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3 mt-2">Create your menu</h3>
                        <p class="text-slate-600 leading-relaxed">Add categories and dishes with photos, prices, descriptions. Organize everything your way.</p>
                    </div>
                    <div class="relative bg-white rounded-2xl border-2 border-slate-100 p-6 lg:p-8 hover:border-orange-200 hover:shadow-lg transition-all group">
                        <div class="absolute -top-4 left-6 w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center font-bold text-lg shadow-lg group-hover:scale-110 transition-transform">2</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3 mt-2">Customize design</h3>
                        <p class="text-slate-600 leading-relaxed">Choose layouts, fonts, RTL for Arabic, price positions, and more. Make it yours.</p>
                    </div>
                    <div class="relative bg-white rounded-2xl border-2 border-slate-100 p-6 lg:p-8 hover:border-orange-200 hover:shadow-lg transition-all group">
                        <div class="absolute -top-4 left-6 w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center font-bold text-lg shadow-lg group-hover:scale-110 transition-transform">3</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3 mt-2">Share with customers</h3>
                        <p class="text-slate-600 leading-relaxed">Get a unique link and QR code. Print it, share on social, or display at your venue.</p>
                    </div>
                    <div class="relative bg-white rounded-2xl border-2 border-slate-100 p-6 lg:p-8 hover:border-orange-200 hover:shadow-lg transition-all group">
                        <div class="absolute -top-4 left-6 w-10 h-10 rounded-xl bg-orange-500 text-white flex items-center justify-center font-bold text-lg shadow-lg group-hover:scale-110 transition-transform">4</div>
                        <h3 class="text-xl font-bold text-slate-900 mb-3 mt-2">Track performance</h3>
                        <p class="text-slate-600 leading-relaxed">See visits, unique visitors, time spent, bounce rate. Understand how customers use your menu.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 sm:py-28 bg-slate-50 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Everything you need</h2>
                <p class="text-lg text-slate-600 max-w-2xl mx-auto">Professional tools to build and manage your digital menu</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">QR Code</h3>
                    <p class="text-slate-600 text-xs">Generate and download QR codes. Scan to open menu instantly.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Analytics & Stats</h3>
                    <p class="text-slate-600 text-xs">Visits, unique visitors, page views, time spent, bounce rate.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">RTL & Arabic</h3>
                    <p class="text-slate-600 text-xs">Full RTL support. Arabic fonts. Bilingual owner dashboard.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-violet-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Flexible layouts</h3>
                    <p class="text-slate-600 text-xs">Grid, tabs, list, cards. Collapsible categories. Multiple dish styles.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-rose-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">30+ Custom fonts</h3>
                    <p class="text-slate-600 text-xs">Google Fonts including Arabic. Perfect typography.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-cyan-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Dual currency</h3>
                    <p class="text-slate-600 text-xs">Two currencies with exchange rate. Perfect for tourists.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-sky-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Social links</h3>
                    <p class="text-slate-600 text-xs">Instagram, Facebook, WhatsApp. Let customers find you.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-teal-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Mobile-first</h3>
                    <p class="text-slate-600 text-xs">Optimized for phones and tablets. Fast, touch-friendly.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Dish & category images</h3>
                    <p class="text-slate-600 text-xs">Upload photos. Auto-optimized for fast loading.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-pink-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Restaurant branding</h3>
                    <p class="text-slate-600 text-xs">Logo, cover image, name, address, phone on menu.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-lime-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-lime-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Ingredients & allergens</h3>
                    <p class="text-slate-600 text-xs">Display ingredients. Add allergen info per dish.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-fuchsia-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-fuchsia-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11.5V14m0-2.5v-6a1.5 1.5 0 113 0m-3 6a1.5 1.5 0 00-3 0v2a7.5 7.5 0 0015 0v-5a1.5 1.5 0 00-3 0m-6-3V11m0-5.5v-1a1.5 1.5 0 013 0v1m0 0V11m0-5.5a1.5 1.5 0 013 0v3m0 0V11"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Price display options</h3>
                    <p class="text-slate-600 text-xs">Next to title or on image. Top/bottom, left/right.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Share button</h3>
                    <p class="text-slate-600 text-xs">Let visitors share your menu. Configurable position.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Loading page</h3>
                    <p class="text-slate-600 text-xs">Optional splash with your logo before menu loads.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">Restaurant & Home Cook</h3>
                    <p class="text-slate-600 text-xs">Menu styles for dine-in or casual home menus.</p>
                </div>
                <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100 hover:shadow-md hover:border-orange-100 transition-all">
                    <div class="w-10 h-10 rounded-lg bg-emerald-100 flex items-center justify-center mb-3"><svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg></div>
                    <h3 class="font-semibold text-slate-900 mb-1.5 text-sm">SEO optimized</h3>
                    <p class="text-slate-600 text-xs">Meta tags, Open Graph. Your menu is discoverable.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Pricing Section -->
    <section id="pricing" class="py-20 sm:py-28 bg-white scroll-mt-20">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl sm:text-4xl font-bold text-slate-900 mb-4">Simple pricing</h2>
                <p class="text-lg text-slate-600">Start free. Upgrade when you need more.</p>
            </div>
            <div class="relative">
                <div class="absolute -inset-1 bg-gradient-to-r from-orange-500 via-amber-500 to-orange-500 rounded-[2rem] blur-sm opacity-20"></div>
                <div class="relative bg-white rounded-3xl shadow-2xl shadow-slate-200/60 border-2 border-orange-100 overflow-hidden">
                    <div class="bg-gradient-to-r from-orange-600 to-orange-500 px-8 py-6 text-center">
                        <p class="text-orange-100 text-sm font-semibold uppercase tracking-wider mb-2">Free plan</p>
                        <div class="inline-block">
                            <span class="text-5xl sm:text-6xl font-black text-white tracking-tight">FREE</span>
                        </div>
                        <p class="text-orange-100 mt-2 text-lg">All features included. No credit card.</p>
                    </div>
                    <div class="p-8 sm:p-10">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-6 mb-8">
                            <div>
                                <h3 class="text-2xl font-bold text-slate-900">Everything you need</h3>
                                <p class="text-slate-600 mt-1">20 categories • Up to 80 dishes</p>
                            </div>
                            @auth
                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-orange-500 text-white font-semibold hover:bg-orange-600 transition-all shadow-lg shadow-orange-500/25 shrink-0">Go to Dashboard</a>
                            @else
                                <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-orange-500 text-white font-semibold hover:bg-orange-600 transition-all shadow-lg shadow-orange-500/25 shrink-0">Get Started — It's Free</a>
                            @endauth
                        </div>
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-slate-700">20 categories</span></div>
                            <div class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-slate-700">Up to 80 dishes</span></div>
                            <div class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-slate-700">QR code & shareable link</span></div>
                            <div class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-slate-700">Analytics & statistics</span></div>
                            <div class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-slate-700">All layouts & customization</span></div>
                            <div class="flex items-start gap-3"><svg class="w-5 h-5 text-emerald-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg><span class="text-slate-700">RTL & Arabic support</span></div>
                        </div>
                        <div class="mt-8 pt-6 border-t border-slate-200 bg-slate-50 -mx-8 sm:-mx-10 -mb-8 sm:-mb-10 px-8 sm:px-10 py-6">
                            <p class="text-slate-600 text-sm">Need more? <span class="font-semibold text-slate-800">Contact the admin</span> for custom plans with unlimited categories and dishes.</p>
                            <p class="text-slate-600 text-sm mt-2"><a href="tel:+96103004699" class="text-orange-600 font-medium hover:text-orange-700">+961 03 004 699</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 sm:py-28 bg-gradient-to-br from-orange-600 to-orange-500">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl sm:text-4xl font-bold text-white mb-4">Ready to create your menu?</h2>
            <p class="text-xl text-orange-100 mb-10">Join restaurants already using MenuX</p>
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-white text-orange-600 font-semibold text-lg hover:bg-amber-50 transition-colors shadow-lg">Go to Dashboard</a>
            @else
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('register') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-white text-orange-600 font-semibold text-lg hover:bg-amber-50 transition-colors shadow-lg">Create Free Account</a>
                    <a href="{{ route('login') }}" class="inline-flex items-center justify-center px-8 py-4 rounded-xl bg-white/20 text-white font-semibold text-lg border-2 border-white/40 hover:bg-white/30 transition-colors">Sign In</a>
                </div>
            @endauth
        </div>
    </section>

    <!-- Footer / Contact -->
    <footer id="contact" class="bg-slate-900 text-white scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-12 lg:gap-8">
                <div class="lg:col-span-1">
                    <a href="{{ url('/') }}#hero" class="flex items-center gap-3 mb-4">
                        <img src="{{ asset('images/logo/logo.png') }}" alt="MenuX" class="h-12 w-auto brightness-0 invert opacity-95">
                        <span class="font-bold text-xl text-white">MenuX</span>
                    </a>
                    <p class="text-slate-400 text-sm leading-relaxed">Create beautiful digital menus for your restaurant. Free to start, easy to use.</p>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Quick links</h4>
                    <ul class="space-y-3">
                        <li><a href="#how-it-works" class="text-slate-400 hover:text-orange-400 transition-colors text-sm">How it works</a></li>
                        <li><a href="#features" class="text-slate-400 hover:text-orange-400 transition-colors text-sm">Features</a></li>
                        <li><a href="#pricing" class="text-slate-400 hover:text-orange-400 transition-colors text-sm">Pricing</a></li>
                        @guest
                        <li><a href="{{ route('login') }}" class="text-slate-400 hover:text-orange-400 transition-colors text-sm">Sign In</a></li>
                        <li><a href="{{ route('register') }}" class="text-slate-400 hover:text-orange-400 transition-colors text-sm">Register</a></li>
                        @endguest
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Contact</h4>
                    <ul class="space-y-4">
                        <li>
                            <a href="tel:+96103004699" class="flex items-center gap-3 text-slate-400 hover:text-orange-400 transition-colors group">
                                <span class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center group-hover:bg-orange-500/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                </span>
                                <span>+961 03 004 699</span>
                            </a>
                        </li>
                        <li>
                            <a href="mailto:dany.a.seifeddine@gmail.com" class="flex items-center gap-3 text-slate-400 hover:text-orange-400 transition-colors group">
                                <span class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center group-hover:bg-orange-500/20 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                </span>
                                <span class="break-all">dany.a.seifeddine@gmail.com</span>
                            </a>
                        </li>
                        <li>
                            <div class="flex items-start gap-3 text-slate-400">
                                <span class="w-9 h-9 rounded-lg bg-slate-800 flex items-center justify-center flex-shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                </span>
                                <span>Barja, Lebanon</span>
                            </div>
                        </li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold text-white mb-4 text-sm uppercase tracking-wider">Get started</h4>
                    <p class="text-slate-400 text-sm mb-4">Create your free menu in minutes.</p>
                    @guest
                    <a href="{{ route('register') }}" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 transition-colors">Get Started Free</a>
                    @else
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center px-5 py-2.5 rounded-lg bg-orange-500 text-white text-sm font-semibold hover:bg-orange-600 transition-colors">Dashboard</a>
                    @endguest
                </div>
            </div>
            <div class="border-t border-slate-800 mt-12 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-slate-500 text-sm">&copy; {{ date('Y') }} MenuX. All rights reserved.</p>
                <p class="text-slate-500 text-sm">Based in Lebanon</p>
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
