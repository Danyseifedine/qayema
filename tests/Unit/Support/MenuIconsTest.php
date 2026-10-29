<?php

namespace Tests\Unit\Support;

use App\Models\RestaurantSocialLink;
use App\Support\MenuIcons;
use PHPUnit\Framework\TestCase;

class MenuIconsTest extends TestCase
{
    private const TEMPLATES = __DIR__.'/../../../resources/views/menu/templates';

    public function test_it_offers_exactly_the_known_icons(): void
    {
        $this->assertSame([
            'clock', 'pin', 'table', 'phone', 'search', 'cart', 'back', 'plus', 'close', 'check', 'chevron', 'minus', 'language',
            'qr', 'whatsapp', 'top', 'menu', 'heart', 'instagram', 'facebook', 'x', 'tiktok',
            'youtube', 'snapchat', 'threads', 'telegram', 'pinterest', 'tripadvisor',
        ], array_keys(MenuIcons::all()));
    }

    public function test_every_icon_is_one_well_formed_24px_svg(): void
    {
        foreach (MenuIcons::all() as $name => $svg) {
            $this->assertStringStartsWith('<svg ', $svg, "[{$name}] is not an svg.");
            $this->assertStringEndsWith('</svg>', $svg, "[{$name}] is not closed.");
            $this->assertSame(1, substr_count($svg, '<svg'), "[{$name}] nests svgs.");

            $xml = simplexml_load_string($svg);

            $this->assertNotFalse($xml, "[{$name}] is not well-formed.");
            $this->assertSame('svg', $xml->getName());
            $this->assertSame('0 0 24 24', (string) $xml['viewBox'], "[{$name}] has a different viewBox.");
            $this->assertSame('none', (string) $xml['fill'], "[{$name}] fills its own shapes.");
            $this->assertGreaterThan(0, $xml->count(), "[{$name}] draws nothing.");
        }
    }

    /** Screen readers skip them: the text next to each icon says what it is. */
    public function test_every_icon_is_hidden_from_assistive_technology(): void
    {
        foreach (MenuIcons::all() as $name => $svg) {
            $this->assertStringContainsString('aria-hidden="true"', $svg, "[{$name}] is announced.");
        }
    }

    /** Icons take the text colour around them, so a menu's colours reach them. */
    public function test_every_icon_draws_in_the_current_text_colour(): void
    {
        foreach (MenuIcons::all() as $name => $svg) {
            $this->assertStringContainsString('stroke="currentColor"', $svg, "[{$name}] ignores the text colour.");
            $this->assertDoesNotMatchRegularExpression('/(fill|stroke)="#/', $svg, "[{$name}] hard-codes a colour.");
        }
    }

    public function test_nothing_but_inline_markup_is_in_an_icon(): void
    {
        foreach (MenuIcons::all() as $name => $svg) {
            $this->assertStringNotContainsStringIgnoringCase('<script', $svg, "[{$name}] carries a script.");
            $this->assertStringNotContainsStringIgnoringCase('href', $svg, "[{$name}] links out.");
            $this->assertStringNotContainsString('{{', $svg);
        }
    }

    public function test_every_social_platform_has_its_own_icon(): void
    {
        foreach (RestaurantSocialLink::platforms() as $platform) {
            $this->assertArrayHasKey($platform, MenuIcons::all(), "[{$platform}] falls back to the heart.");
        }
    }

    /** A key a menu design asks for that is missing would be a PHP warning on a live menu. */
    public function test_every_icon_a_menu_design_names_exists(): void
    {
        $views = glob(self::TEMPLATES.'/*.blade.php') ?: [];

        $this->assertContains('classic.blade.php', array_map('basename', $views));
        $this->assertContains('ember.blade.php', array_map('basename', $views));

        foreach ($views as $path) {
            $view = (string) file_get_contents($path);
            $design = basename($path, '.blade.php');

            preg_match_all("/\\\$icons\\['([a-z]+)'\\]/", $view, $direct);
            preg_match_all("/'icon' => '([a-z]+)'/", $view, $facts);

            $named = array_unique([...$direct[1], ...$facts[1]]);

            $this->assertNotEmpty($named, $design);

            foreach ($named as $name) {
                $this->assertArrayHasKey($name, MenuIcons::all(), "The {$design} design asks for a missing [{$name}] icon.");
            }
        }
    }
}
