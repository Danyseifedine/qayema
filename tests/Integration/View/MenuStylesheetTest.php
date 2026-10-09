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
     * A dish photo is shown whole, in its own shape, wherever it appears:
     * never cropped to a box, and with no grey box behind it.
     */
    public function test_dish_photos_are_never_cropped_or_boxed(): void
    {
        $css = (string) file_get_contents(public_path('css/menu-classic.css'));

        foreach (['dish-photo', 'cart-line-photo', 'dish-sheet-photo'] as $class) {
            preg_match('/\\.'.$class.'\\s*\\{([^}]*)\\}/', $css, $rule);
            $this->assertNotEmpty($rule, $class);
            $this->assertStringNotContainsString('object-fit: cover', $rule[1], $class);
            $this->assertStringNotContainsString('background', $rule[1], $class);
            $this->assertStringNotContainsString('aspect-ratio', $rule[1], $class);
            $this->assertStringContainsString('height: auto', $rule[1], $class);
        }
    }
}
