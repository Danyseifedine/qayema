<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        <!-- Google Fonts for Menu Settings -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600;700&family=Lato:wght@400;700&family=Montserrat:wght@400;600;700&family=Poppins:wght@400;500;600;700&family=Raleway:wght@400;600;700&family=Playfair+Display:wght@400;700&family=Merriweather:wght@400;700&family=Nunito:wght@400;600;700&family=Ubuntu:wght@400;500;700&family=Oswald:wght@400;600;700&family=Source+Sans+Pro:wght@400;600;700&family=PT+Sans:wght@400;700&family=Noto+Sans:wght@400;700&family=Work+Sans:wght@400;600;700&family=Rubik:wght@400;500;700&family=Quicksand:wght@400;600;700&family=Karla:wght@400;700&family=DM+Sans:wght@400;500;700&family=Manrope:wght@400;600;700&family=Outfit:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;600;700&family=Space+Grotesk:wght@400;600;700&family=Josefin+Sans:wght@400;600;700&family=Crimson+Text:wght@400;600;700&family=Lora:wght@400;700&family=Libre+Baskerville:wght@400;700&family=PT+Serif:wght@400;700&family=EB+Garamond:wght@400;700&family=Cormorant+Garamond:wght@400;700&family=Libre+Caslon+Text:wght@400;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            @if (is_impersonating())
                <div class="bg-amber-500 text-white">
                    <div class="max-w-7xl mx-auto py-2 px-4 sm:px-6 lg:px-8 flex items-center justify-between flex-wrap gap-2">
                        <span class="font-medium">You are viewing as {{ auth()->user()->name }}.</span>
                        <a href="{{ route('impersonate.leave') }}"
                            class="underline font-semibold hover:text-amber-100">
                            Leave impersonation
                        </a>
                    </div>
                </div>
            @endif

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>

            @auth
                @if(auth()->user()->isMenuOwner() || auth()->user()->isAdmin())
                    @php
                        $locales = [
                            'en' => ['name' => 'English',   'flag' => '🇺🇸'],
                            'ar' => ['name' => 'العربية',   'flag' => '🇸🇦'],
                            'fr' => ['name' => 'Français',  'flag' => '🇫🇷'],
                            'de' => ['name' => 'Deutsch',   'flag' => '🇩🇪'],
                            'es' => ['name' => 'Español',   'flag' => '🇪🇸'],
                            'it' => ['name' => 'Italiano',  'flag' => '🇮🇹'],
                            'hi' => ['name' => 'हिन्दी',    'flag' => '🇮🇳'],
                            'pt' => ['name' => 'Português', 'flag' => '🇧🇷'],
                            'ru' => ['name' => 'Русский',   'flag' => '🇷🇺'],
                            'tr' => ['name' => 'Türkçe',    'flag' => '🇹🇷'],
                        ];
                        $currentLocale = app()->getLocale();
                        $currentLocaleData = $locales[$currentLocale] ?? $locales['en'];
                    @endphp

                    <div class="fixed bottom-6 right-6 rtl:right-auto rtl:left-6 z-50 flex flex-col items-end gap-2"
                         x-data="{ open: false }"
                         @click.outside="open = false">

                        {{-- Dropdown list — sits above button via flex-col order --}}
                        <div x-show="open"
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 translate-y-2"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             x-transition:leave="transition ease-in duration-100"
                             x-transition:leave-start="opacity-100 translate-y-0"
                             x-transition:leave-end="opacity-0 translate-y-2"
                             class="w-48 bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden"
                             style="display: none;">
                            <div class="py-1 max-h-72 overflow-y-auto">
                                @foreach($locales as $locale => $info)
                                    <a href="{{ route('owner.locale.switch', ['locale' => $locale]) }}"
                                       class="flex items-center gap-3 px-4 py-2.5 text-sm transition-colors {{ $currentLocale === $locale ? 'bg-orange-50 text-orange-700 font-semibold' : 'text-gray-700 hover:bg-gray-50' }}">
                                        <span class="text-base leading-none">{{ $info['flag'] }}</span>
                                        <span>{{ $info['name'] }}</span>
                                        @if($currentLocale === $locale)
                                            <svg class="w-4 h-4 ml-auto rtl:ml-0 rtl:mr-auto shrink-0 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>

                        {{-- Toggle button --}}
                        <button @click="open = !open"
                                aria-label="{{ __('menu_owner.language_switcher.aria') }}"
                                class="inline-flex items-center gap-2 px-3 py-2 rounded-lg bg-orange-500 text-white text-sm font-medium shadow-sm hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 transition">
                            <span class="text-base leading-none">{{ $currentLocaleData['flag'] }}</span>
                            <span>{{ $currentLocaleData['name'] }}</span>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                @endif
            @endauth
        </div>
    </body>
</html>
