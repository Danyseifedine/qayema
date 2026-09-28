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
}
