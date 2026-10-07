<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What menu.partials.theme gives every design: the owner's fonts, and the
 * design's colours as CSS variables.
 */
class MenuThemeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Appearance, Feature::MultipleLanguages, Feature::QrStudio);
    }

    private function menu(Restaurant $restaurant, string $query = ''): string
    {
        return $this->get(route('public.menu', $restaurant->slug).$query)->assertOk()->getContent();
    }

    private function classic(): Template
    {
        return Template::factory()->withSettings(Template::CLASSIC_SCHEMA)->create(['slug' => 'classic']);
    }

    public function test_every_colour_the_design_declares_becomes_a_css_variable(): void
    {
        $classic = $this->classic();
        $restaurant = $this->published(['template_id' => $classic->id, 'template_settings' => [$classic->id => ['text_color' => '#222222']]]);

        $html = $this->menu($restaurant);

        $this->assertStringContainsString('--primary-color: '.Template::DEFAULT_PRIMARY_COLOR.';', $html);
        $this->assertStringContainsString('--primary-color-ink: #111418;', $html);
        $this->assertStringContainsString('--text-color: #222222;', $html);
        $this->assertStringContainsString('--text-color-ink: #FFFFFF;', $html);
    }

    /**
     * The page scrollbar and form controls are the browser's own: told the
     * background is dark, it draws them dark instead of grey on white.
     */
    public function test_a_dark_background_asks_the_browser_for_its_dark_parts(): void
    {
        $classic = $this->classic();
        $restaurant = $this->published(['template_id' => $classic->id]);

        $this->assertStringContainsString('color-scheme: light;', $this->menu($restaurant));

        $restaurant->saveDesignSettings($classic, ['background_color' => '#121212']);

        $this->assertStringContainsString('color-scheme: dark;', $this->menu($restaurant->fresh()));
    }

    public function test_the_owner_can_hide_the_name_beside_the_logo(): void
    {
        $classic = $this->classic();
        $restaurant = $this->published(['template_id' => $classic->id, 'name' => ['en' => 'Beit El Deek']]);

        $this->assertStringContainsString('<span class="brand-name">Beit El Deek</span>', $this->menu($restaurant));

        $restaurant->saveDesignSettings($classic, ['show_name' => false]);
        $html = $this->menu($restaurant->fresh());

        $this->assertStringNotContainsString('class="brand-name"', $html);
        // Still named for a screen reader, and still in the cover heading.
        $this->assertStringContainsString('<a class="brand" href="#top" aria-label="Beit El Deek">', $html);
    }

    public function test_an_on_off_default_typed_as_text_in_the_admin_reads_as_off(): void
    {
        $design = Template::factory()->withSettings([['key' => 'show_name', 'type' => 'boolean', 'default' => 'false']])->create(['slug' => 'classic']);
        $restaurant = $this->published(['template_id' => $design->id]);

        $this->assertStringNotContainsString('class="brand-name"', $this->menu($restaurant));
    }

    public function test_a_value_that_is_not_a_colour_never_reaches_the_stylesheet(): void
    {
        $classic = $this->classic();
        // Only reachable by writing the column directly; the API refuses it.
        $restaurant = $this->published(['template_id' => $classic->id, 'template_settings' => [$classic->id => ['text_color' => '#fff;}body{display:none']]]);

        $html = $this->menu($restaurant);

        $this->assertStringNotContainsString('display:none', $html);
        $this->assertStringContainsString('--text-color: #111418;', $html);
    }

    public function test_a_latin_menu_loads_only_the_latin_pick(): void
    {
        $restaurant = $this->published(['second_locale' => 'es', 'menu_fonts' => ['latin' => 'Playfair Display']]);

        $html = $this->menu($restaurant);

        $this->assertStringContainsString('family=Playfair+Display:wght@400;500;600;700', $html);
        $this->assertStringContainsString("--font: 'Playfair Display', -apple-system", $html);
        $this->assertStringNotContainsString('family=Inter', $html);
    }

    public function test_an_arabic_menu_draws_latin_text_in_the_latin_pick(): void
    {
        $restaurant = $this->published(['second_locale' => 'ar', 'menu_fonts' => ['latin' => 'Poppins', 'arabic' => 'Cairo']]);

        $html = $this->menu($restaurant, '?lang=ar');

        // Latin first so prices and brand names keep the Latin pick; Cairo
        // takes every Arabic letter Poppins lacks.
        $this->assertStringContainsString("--font: 'Poppins', 'Cairo', -apple-system", $html);
        $this->assertStringContainsString('family=Poppins:wght@400;500;600;700&amp;family=Cairo:wght@400;500;600;700', $html);

        // The English page of the same menu needs no Arabic font.
        $this->assertStringNotContainsString('family=Cairo', $this->menu($restaurant, '?lang=en'));
    }

    public function test_a_script_whose_letters_latin_fonts_also_carry_puts_its_own_pick_first(): void
    {
        $restaurant = $this->published(['second_locale' => 'ru', 'menu_fonts' => ['latin' => 'Poppins', 'cyrillic' => 'PT Serif']]);

        $html = $this->menu($restaurant, '?lang=ru');

        $this->assertStringContainsString("--font: 'PT Serif', 'Poppins', -apple-system", $html);
        // PT Serif only has two weights; asking for more would 400 at Google.
        $this->assertStringContainsString('family=PT+Serif:wght@400;700', $html);
    }

    public function test_the_defaults_keep_todays_fonts(): void
    {
        $restaurant = $this->published(['second_locale' => 'ar']);

        $this->assertStringContainsString("--font: 'Inter', 'El Messiri', -apple-system", $this->menu($restaurant, '?lang=ar'));
        $this->assertStringContainsString("--font: 'Inter', -apple-system", $this->menu($restaurant, '?lang=en'));
    }

    public function test_a_preview_of_another_design_shows_the_colours_saved_for_it(): void
    {
        $classic = $this->classic();
        $midnight = Template::factory()->withSettings(Template::CLASSIC_SCHEMA)->create(['slug' => 'midnight']);
        $restaurant = $this->published(['template_id' => $classic->id, 'template_settings' => [$midnight->id => ['primary_color' => '#1F6FEB']]]);

        $this->actingAs($restaurant->user)->get(route('public.menu', $restaurant->slug).'?preview='.$midnight->id)
            ->assertOk()
            ->assertSee('--accent: #1F6FEB', false);

        // Its own menu is untouched: the colour belongs to the other design.
        $this->assertStringContainsString('--accent: '.Template::DEFAULT_PRIMARY_COLOR, $this->menu($restaurant));
    }

    public function test_the_qr_card_carries_every_font_the_menu_uses(): void
    {
        $restaurant = $this->published(['second_locale' => 'ar', 'menu_fonts' => ['arabic' => 'Amiri']]);

        $this->get(route('public.qr', $restaurant->slug))
            ->assertOk()
            ->assertSee('family=Inter:wght@400;500;600;700&amp;family=Amiri:wght@400;700', false)
            ->assertSee("--font: 'Inter', 'Amiri', system-ui", false)
            ->assertDontSee('El+Messiri', false);
    }
}
