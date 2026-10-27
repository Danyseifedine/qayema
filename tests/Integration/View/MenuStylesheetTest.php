<?php

namespace Tests\Integration\View;

use Tests\TestCase;

/**
 * The classic menu's stylesheet (public/css/menu-classic.css), which every new
 * design is scaffolded from. menu-nav.js filters the menu by setting the
 * `hidden` attribute on dishes and sections, so the stylesheet must never let
 * a `display` rule win over it.
 */
class MenuStylesheetTest extends TestCase
{
    public function test_the_hidden_attribute_always_hides_what_search_and_tabs_filter_out(): void
    {
        $css = (string) file_get_contents(public_path('css/menu-classic.css'));

        // `.dish { display: flex }` alone would override the browser's own
        // [hidden] rule and leave a dish that does not match on screen.
        $this->assertMatchesRegularExpression('/\.dish\s*\{[^}]*display:\s*flex/', $css);
        $this->assertMatchesRegularExpression('/(^|\n)\[hidden\]\s*\{\s*display:\s*none\s*!important;\s*\}/', $css);
    }

    /**
     * The bar back to the guest's order, on a narrow phone: the number stays
     * on one line, the status wraps rather than being cut mid-word, and the
     * cart mark gives up its room on the smallest screens.
     */
    public function test_the_order_bar_fits_a_small_phone(): void
    {
        $css = (string) file_get_contents(public_path('css/menu-classic.css'));

        $this->assertMatchesRegularExpression('/\.order-bar-text strong\s*\{[^}]*white-space:\s*nowrap/', $css);
        $this->assertDoesNotMatchRegularExpression('/\.order-bar-status\s*\{[^}]*white-space:\s*nowrap/', $css);
        $this->assertMatchesRegularExpression('/@media \(max-width: 380px\)\s*\{\s*\.order-bar-mark\s*\{\s*display:\s*none;/', $css);
    }

    /**
     * The small photos (a card, a cart line) fill their slot, trimmed at the
     * edges, so every card looks the same; the big one (the dish sheet) is
     * the whole photo. None has a box drawn behind it.
     */
    public function test_small_photos_fill_their_slot_and_the_big_one_is_whole(): void
    {
        $css = (string) file_get_contents(public_path('css/menu-classic.css'));
        $rule = function (string $class) use ($css): string {
            preg_match('/\\.'.$class.'\\s*\\{([^}]*)\\}/', $css, $match);
            $this->assertNotEmpty($match, $class);

            return $match[1];
        };

        foreach (['dish-photo', 'cart-line-photo'] as $class) {
            $this->assertStringContainsString('object-fit: cover', $rule($class), $class);
            $this->assertStringContainsString('width: 100%', $rule($class), $class);
            $this->assertStringContainsString('height: 100%', $rule($class), $class);
            $this->assertStringNotContainsString('background', $rule($class), $class);
        }

        $this->assertStringContainsString('object-fit: contain', $rule('dish-sheet-photo'));
        $this->assertStringContainsString('height: auto', $rule('dish-sheet-photo'));
        $this->assertStringNotContainsString('background', $rule('dish-sheet-photo'));
    }

    /**
     * Whatever a photo's shape, it fills a slot of one size, so every
     * dish's name starts in the same place down the menu (and the cart).
     */
    public function test_every_dish_photo_sits_in_a_slot_of_one_size(): void
    {
        $css = (string) file_get_contents(public_path('css/menu-classic.css'));

        foreach (['dish-photo-slot' => ['96px', '72px'], 'cart-line-photo-slot' => ['72px', '56px']] as $class => [$width, $height]) {
            preg_match('/\\.'.$class.'\\s*\\{([^}]*)\\}/', $css, $rule);
            $this->assertNotEmpty($rule, $class);
            $this->assertStringContainsString("width: {$width}", $rule[1], $class);
            $this->assertStringContainsString("height: {$height}", $rule[1], $class);
            $this->assertStringContainsString('flex-shrink: 0', $rule[1], $class);
        }

    }

    /**
     * On a wide screen a banner is a strip as wide as the cover: not its
     * phone shape left alone beside empty space. Its picture keeps its own
     * proportions on the far side, mirrored on an Arabic menu, and the ad
     * sits in the menu's corner rather than over the dishes.
     */
    public function test_a_banner_is_a_full_width_strip_on_a_wide_screen(): void
    {
        $css = (string) file_get_contents(public_path('css/menu-classic.css'));
        $wide = (string) preg_replace('/^.*?\/\* On a wide screen a banner/s', '', $css);

        $this->assertMatchesRegularExpression('/\.offer-banner\s*\{[^}]*flex-basis:\s*100%;[^}]*aspect-ratio:\s*auto;[^}]*height:\s*var\(--strip\)/', $wide);
        $this->assertMatchesRegularExpression('/\.offer-banner:not\(\.offer-picture-only\) \.offer-photo\s*\{[^}]*width:\s*min\(calc\(var\(--strip\) \* var\(--ratio\)\)/', $wide);
        $this->assertStringContainsString('[dir="rtl"] .offer-banner:not(.offer-picture-only) .offer-photo', $wide);
        $this->assertMatchesRegularExpression('/\.offer-ad-wrap\s*\{[^}]*inset-inline:\s*32px auto;/', $wide);
    }

    /**
     * A dish's discount sits on its photo's corner, over the picture, so the
     * card keeps its height; without a photo it is beside the price.
     */
    public function test_a_dish_discount_takes_no_room_on_its_card(): void
    {
        $css = (string) file_get_contents(public_path('css/menu-classic.css'));
        $js = (string) file_get_contents(public_path('js/menu-offers.js'));

        $this->assertMatchesRegularExpression('/\.dish-photo-slot\s*\{\s*position:\s*relative;/', $css);
        $this->assertMatchesRegularExpression('/\.dish-photo-slot \.dish-badge\s*\{[^}]*position:\s*absolute;[^}]*inset-inline-start:/', $css);
        $this->assertStringContainsString("(dish.querySelector('.dish-photo-slot') || group || dish).appendChild(badge)", $js);
    }
}
