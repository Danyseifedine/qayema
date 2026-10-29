<?php

namespace Tests\Integration\Database;

use App\Models\Template;
use App\Support\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Ember's row, written by its migration (deploy runs it): a premium dark
 * design, written whole over a row make:menu-template left, and rolled back
 * without taking any menu offline.
 */
class AddTheEmberDesignTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function ember(): Template
    {
        return Template::query()->where('slug', 'ember')->firstOrFail();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_29_000001_add_the_ember_design.php');
    }

    public function test_its_migration_adds_it_as_a_premium_dark_design(): void
    {
        $ember = $this->ember();

        $this->assertTrue($ember->is_premium);
        $this->assertTrue($ember->is_active);
        $this->assertNull($ember->restaurant_id);
        $this->assertSame('Ember', $ember->getTranslation('name', 'en'));
        $this->assertSame('جمر', $ember->getTranslation('name', 'ar'));
        $this->assertSame(['primary_color', 'background_color', 'text_color', 'show_name'], array_column($ember->settingsSchema(), 'key'));

        $defaults = $ember->defaultSettings();
        $this->assertTrue(Color::isDark($defaults['background_color']));
        $this->assertFalse(Color::isDark($defaults['text_color']));
        $this->assertTrue($defaults['show_name']);
        foreach ($ember->settingsSchema() as $row) {
            $this->assertTrue(Template::accepts($row, $row['default']), $row['key']);
        }
        // The gold prices and the text are read on the dark page.
        $this->assertSame('background_color', collect($ember->settingsSchema())->firstWhere('key', 'text_color')['contrast_with']);
        $this->assertSame('background_color', collect($ember->settingsSchema())->firstWhere('key', 'primary_color')['contrast_with']);
    }

    /** make:menu-template may have written a row already: running it again leaves one, written whole. */
    public function test_its_migration_runs_again_over_a_row_already_there(): void
    {
        $this->ember()->update(['is_premium' => false, 'settings_schema' => Template::CLASSIC_SCHEMA]);

        $this->migration()->up();

        $this->assertSame(1, Template::query()->where('slug', 'ember')->count());
        $this->assertTrue($this->ember()->is_premium);
        $this->assertSame('#0E0C0A', $this->ember()->defaultSettings()['background_color']);
    }

    public function test_rolling_it_back_moves_its_menus_to_classic_and_forgets_their_colours(): void
    {
        $ember = $this->ember();
        $restaurant = $this->published(['template_id' => $ember->id, 'template_settings' => [$ember->id => ['primary_color' => '#AA7733']]]);
        $classic = Template::query()->where('slug', 'classic')->firstOrFail();

        $this->migration()->down();

        $this->assertFalse(Template::query()->where('slug', 'ember')->exists());
        $this->assertSame($classic->id, $restaurant->fresh()->template_id);
        $this->assertNull($restaurant->fresh()->template_settings);

        $this->migration()->up();
        $this->assertTrue($this->ember()->is_premium);
    }
}
