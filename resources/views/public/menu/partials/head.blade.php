@php
    $menuImage = $user->hasMedia('cover_image')
        ? $user->getFirstMediaUrl('cover_image')
        : ($user->hasMedia('logo')
            ? $user->getFirstMediaUrl('logo')
            : asset('images/logo/logo.png'));

    $menuDescription =
        $menu->description ?:
        "View the digital menu for {$user->restaurant_name}. Browse our delicious dishes and place your order.";

    // Get font family for dynamic font loading
    $fontFamily = $settings['font_family'] ?? 'sans';
    $fontMap = [
        'sans' => '',
        'serif' => '',
        'mono' => '',
        'cursive' => '',
        'inter' => 'Inter',
        'roboto' => 'Roboto',
        'open-sans' => 'Open Sans',
        'lato' => 'Lato',
        'montserrat' => 'Montserrat',
        'poppins' => 'Poppins',
        'raleway' => 'Raleway',
        'nunito' => 'Nunito',
        'ubuntu' => 'Ubuntu',
        'source-sans-pro' => 'Source Sans Pro',
        'pt-sans' => 'PT Sans',
        'noto-sans' => 'Noto Sans',
        'work-sans' => 'Work Sans',
        'rubik' => 'Rubik',
        'quicksand' => 'Quicksand',
        'karla' => 'Karla',
        'dm-sans' => 'DM Sans',
        'manrope' => 'Manrope',
        'outfit' => 'Outfit',
        'plus-jakarta-sans' => 'Plus Jakarta Sans',
        'space-grotesk' => 'Space Grotesk',
        'josefin-sans' => 'Josefin Sans',
        'playfair' => 'Playfair Display',
        'merriweather' => 'Merriweather',
        'crimson-text' => 'Crimson Text',
        'lora' => 'Lora',
        'libre-baskerville' => 'Libre Baskerville',
        'pt-serif' => 'PT Serif',
        'eb-garamond' => 'EB Garamond',
        'cormorant-garamond' => 'Cormorant Garamond',
        'libre-caslon-text' => 'Libre Caslon Text',
    ];
    $fontName = $fontMap[$fontFamily] ?? '';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-seo :title="$menu->name . ' - ' . ($user->restaurant_name ?? 'Menu')" :description="$menuDescription" :keywords="'menu, restaurant menu, digital menu, ' . ($user->restaurant_name ?? '') . ', ' . ($menu->name ?? '')" :url="route('public.menu', $menu->slug)" :image="$menuImage" :imageAlt="$menu->name . ' - ' . ($user->restaurant_name ?? 'Menu')"
        type="website" :siteName="config('app.name', 'MenuX')" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Google Fonts - Load all fonts for menu designs -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto:wght@400;500;700&family=Open+Sans:wght@400;600;700&family=Lato:wght@400;700&family=Montserrat:wght@400;600;700&family=Poppins:wght@400;500;600;700&family=Raleway:wght@400;600;700&family=Nunito:wght@400;600;700&family=Ubuntu:wght@400;500;700&family=Source+Sans+Pro:wght@400;600;700&family=PT+Sans:wght@400;700&family=Noto+Sans:wght@400;700&family=Work+Sans:wght@400;600;700&family=Rubik:wght@400;500;700&family=Quicksand:wght@400;600;700&family=Karla:wght@400;700&family=DM+Sans:wght@400;500;700&family=Manrope:wght@400;600;700&family=Outfit:wght@400;600;700&family=Plus+Jakarta+Sans:wght@400;600;700&family=Space+Grotesk:wght@400;600;700&family=Josefin+Sans:wght@400;600;700&family=Playfair+Display:wght@400;700&family=Merriweather:wght@400;700&family=Crimson+Text:wght@400;600;700&family=Lora:wght@400;700&family=Libre+Baskerville:wght@400;700&family=PT+Serif:wght@400;700&family=EB+Garamond:wght@400;700&family=Cormorant+Garamond:wght@400;700&family=Libre+Caslon+Text:wght@400;700&display=swap"
        rel="stylesheet">

    <style>
        [x-cloak] {
            display: none !important;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Hide scrollbar for tabs */
        .scrollbar-hide {
            -ms-overflow-style: none;
            /* IE and Edge */
            scrollbar-width: none;
            /* Firefox */
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari, Opera */
        }

        /* Horizontal bounce animation for scroll indicator */
        @keyframes bounce-x {

            0%,
            100% {
                transform: translateX(0);
            }

            50% {
                transform: translateX(4px);
            }
        }

        .animate-bounce-x {
            animation: bounce-x 1s ease-in-out infinite;
        }

        /* Dynamic Font Family */
        body {
            @if ($fontFamily === 'sans')
                font-family: system-ui, -apple-system, sans-serif;
            @elseif ($fontFamily === 'serif')
                font-family: Georgia, serif;
            @elseif ($fontFamily === 'mono')
                font-family: 'Courier New', monospace;
            @elseif ($fontFamily === 'cursive')
                font-family: 'Brush Script MT', cursive;
            @elseif ($fontName)
                font-family: '{{ $fontName }}', sans-serif;
            @else
                font-family: system-ui, -apple-system, sans-serif;
            @endif
        }
    </style>
</head>
