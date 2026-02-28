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
                @if(auth()->user()->isMenuOwner())
                    <div class="fixed bottom-6 right-6 rtl:right-auto rtl:left-6 z-50 flex flex-col gap-2" aria-label="{{ __('menu_owner.language_switcher.aria') }}">
                        <a href="{{ route('owner.locale.switch', ['locale' => app()->getLocale() === 'ar' ? 'en' : 'ar']) }}"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium shadow-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition"
                            title="{{ app()->getLocale() === 'ar' ? __('menu_owner.language_switcher.switch_to_english') : __('menu_owner.language_switcher.switch_to_arabic') }}">
                            <span>{{ app()->getLocale() === 'ar' ? __('menu_owner.language_switcher.switch_to_english') : __('menu_owner.language_switcher.switch_to_arabic') }}</span>
                        </a>
                    </div>
                @endif
            @endauth
        </div>
    </body>
</html>
