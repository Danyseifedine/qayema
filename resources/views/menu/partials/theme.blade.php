{{--
    What every menu design gets for free, included in its <head>:

    - the owner's fonts (App\Services\Menu\MenuFonts), loaded from Google Fonts
      with only the weights each family has, and `--font` for the stack;
    - every colour the design declares in its settings_schema as a CSS
      variable: `primary_color` becomes `--primary-color`, plus
      `--primary-color-ink` (near-black or white, whichever reads on it).

    So a new design uses `var(--primary-color)` and `var(--font)` and needs no
    PHP. Expects $restaurant, $template, $settings and $locale.

    A design may draw a few words in a Google face of its own ($tile_font,
    ['family' => ..., 'weight' => ...]): it comes in the same request at
    that one weight and is `--font-tiles`, the owner's fonts after it.
--}}
@use('App\Models\Template')
@use('App\Services\Menu\MenuFonts')
@use('App\Support\Color')
@php
    $menuFonts = MenuFonts::stack($restaurant, $locale);
    $tile_font ??= null;
    $fontsHref = MenuFonts::href($menuFonts, $tile_font === null ? [] : [$tile_font['family'] => $tile_font['weight']]);
    // Only a real hex under a well-formed key reaches the stylesheet: Blade
    // escaping stops HTML, not a `;}` that would break out of the rule.
    $colorVariables = [];
    foreach ($template->colorSettings() as $row) {
        $value = $settings[$row['key']] ?? null;
        if (Color::isHex($value)) {
            $colorVariables[str_replace('_', '-', $row['key'])] = $value;
        }
    }
@endphp
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{{-- Fetched early but never waited on: the menu draws at once in the
     system font and switches when the owner's arrives (display=swap). --}}
<link rel="preload" as="style" href="{{ $fontsHref }}">
<link rel="stylesheet" href="{{ $fontsHref }}" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="{{ $fontsHref }}"></noscript>
<style>
    :root {
@foreach ($colorVariables as $name => $value)
        --{{ $name }}: {{ $value }};
        --{{ $name }}-ink: {{ Color::inkOn($value) }};
@endforeach
        {{-- Quotes cannot go through {{ }}: it escapes them to &#039; and the
             declaration is dropped. The families come from config/fonts.php. --}}
        --font: {!! MenuFonts::css($menuFonts) !!}, -apple-system, BlinkMacSystemFont, 'Segoe UI', system-ui, sans-serif;
@if ($tile_font !== null)
        --font-tiles: {!! MenuFonts::css([$tile_font['family']]) !!}, var(--font);
@endif
    }
</style>
