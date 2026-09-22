{{--
    Template: classic (free)

    Every menu template is a standalone page: it receives $restaurant, $template,
    $settings (the owner's choices merged over the template's defaults) and
    $locale (the language the owner writes in). Copy this file to add a new one —
    the slug of the Blade file must match the template row's slug.
--}}
@php
    $isRtl = in_array($locale, config('locales.rtl', ['ar']), true);
    $logo = $restaurant->getFirstMediaUrl('logo') ?: null;
    $cover = $restaurant->getFirstMediaUrl('cover_image') ?: null;
    $currency = config("currencies.{$restaurant->currency}.symbol", $restaurant->currency);
    $name = $restaurant->getTranslation('name', $locale, false) ?: $restaurant->name;
    $description = $restaurant->getTranslation('description', $locale, false);
    $address = $restaurant->getTranslation('address', $locale, false);
@endphp
<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $isRtl ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $name }}</title>
    <meta name="description" content="{{ $description ?? $name }}">

    <link
        href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600&family=El+Messiri:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <style>
        :root {
            --accent: {{ $settings['primary_color'] ?? '#C8A85A' }};
            --bg: {{ $settings['background_color'] ?? '#FFFFFF' }};
            --text: {{ $settings['text_color'] ?? '#15120A' }};
            --muted: color-mix(in srgb, var(--text) 55%, var(--bg));
            --line: color-mix(in srgb, var(--text) 12%, var(--bg));
            --font: {{ $isRtl ? "'El Messiri'" : "'Geist'" }}, system-ui, sans-serif;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: var(--font);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        .wrap { max-width: 720px; margin: 0 auto; padding: 0 20px 64px; }

        .cover { height: 200px; background: var(--line) center/cover no-repeat; }

        header { text-align: center; padding: 28px 0 8px; }

        .logo {
            width: 92px; height: 92px; border-radius: 50%; object-fit: cover;
            border: 3px solid var(--bg); margin-top: -70px; position: relative;
            background: var(--bg);
        }

        h1 {
            font-family: 'Instrument Serif', serif;
            font-size: clamp(30px, 7vw, 42px);
            margin: 12px 0 4px; font-weight: 400;
        }

        .tagline { color: var(--muted); margin: 0 0 14px; font-size: 15px; }

        .meta { display: flex; flex-wrap: wrap; gap: 8px; justify-content: center; margin-bottom: 8px; }

        .chip {
            display: inline-flex; align-items: center; gap: 6px;
            border: 1px solid var(--line); border-radius: 999px;
            padding: 6px 14px; font-size: 13px; color: var(--muted);
            text-decoration: none;
        }

        .chip:hover { border-color: var(--accent); color: var(--accent); }

        .category { margin-top: 40px; }

        .category > h2 {
            font-family: 'Instrument Serif', serif;
            font-size: 24px; font-weight: 400; margin: 0 0 4px;
            display: flex; align-items: center; gap: 12px;
        }

        .category > h2::after {
            content: ''; flex: 1; height: 1px; background: var(--line);
        }

        .dish {
            display: flex; gap: 14px; align-items: flex-start;
            padding: 16px 0; border-bottom: 1px solid var(--line);
        }

        .dish:last-child { border-bottom: 0; }

        .dish img {
            width: 68px; height: 68px; border-radius: 10px;
            object-fit: cover; flex-shrink: 0;
        }

        .dish-body { flex: 1; min-width: 0; }

        .dish-head { display: flex; gap: 12px; align-items: baseline; }

        .dish-name { font-weight: 500; flex: 1; }

        .price { color: var(--accent); font-weight: 600; white-space: nowrap; }

        .ingredients { color: var(--muted); font-size: 14px; margin: 3px 0 0; }

        .empty { color: var(--muted); text-align: center; padding: 56px 0; }

        footer {
            margin-top: 48px; padding-top: 20px; border-top: 1px solid var(--line);
            text-align: center; color: var(--muted); font-size: 13px;
        }

        footer a { color: var(--accent); text-decoration: none; }
    </style>
</head>

<body>
    @if ($cover)
        <div class="cover" style="background-image:url('{{ $cover }}')"></div>
    @endif

    <div class="wrap">
        <header>
            @if ($logo)
                <img class="logo" src="{{ $logo }}" alt="{{ $name }}" @style(['margin-top:0' => ! $cover])>
            @endif

            <h1>{{ $name }}</h1>

            @if ($description)
                <p class="tagline">{{ $description }}</p>
            @endif

            <div class="meta">
                @if ($restaurant->phone)
                    <a class="chip" href="tel:{{ $restaurant->phone }}">{{ $restaurant->phone }}</a>
                @endif
                @if ($address)
                    @if ($restaurant->google_maps_url)
                        <a class="chip" href="{{ $restaurant->google_maps_url }}" target="_blank" rel="noopener">{{ $address }}</a>
                    @else
                        <span class="chip">{{ $address }}</span>
                    @endif
                @endif
            </div>

            @if ($restaurant->socialLinks->isNotEmpty())
                <div class="meta">
                    @foreach ($restaurant->socialLinks as $link)
                        <a class="chip" href="{{ $link->url }}" target="_blank" rel="noopener">{{ ucfirst($link->platform) }}</a>
                    @endforeach
                </div>
            @endif
        </header>

        @forelse ($restaurant->categories as $category)
            @continue($category->dishes->isEmpty())

            <section class="category">
                <h2>{{ $category->getTranslation('name', $locale, false) ?: $category->name }}</h2>

                @foreach ($category->dishes as $dish)
                    @php
                        $image = $dish->getFirstMediaUrl('image') ?: null;
                        $ingredients = $dish->getTranslation('ingredients', $locale, false);
                    @endphp
                    <article class="dish">
                        @if ($image)
                            <img src="{{ $image }}" alt="{{ $dish->name }}" loading="lazy">
                        @endif
                        <div class="dish-body">
                            <div class="dish-head">
                                <span class="dish-name">{{ $dish->getTranslation('name', $locale, false) ?: $dish->name }}</span>
                                @if ($dish->price !== null)
                                    <span class="price">{{ $currency }}{{ number_format((float) $dish->price, 2) }}</span>
                                @endif
                            </div>
                            @if ($ingredients)
                                <p class="ingredients">{{ $ingredients }}</p>
                            @endif
                        </div>
                    </article>
                @endforeach
            </section>
        @empty
            <p class="empty">{{ __('This menu is still being prepared.') }}</p>
        @endforelse

        <footer>
            <a href="{{ config('app.url') }}">{{ config('app.name', 'Qayema') }}</a>
        </footer>
    </div>
</body>

</html>
