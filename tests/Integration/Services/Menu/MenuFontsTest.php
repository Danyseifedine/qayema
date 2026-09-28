<?php

namespace Tests\Integration\Services\Menu;

use App\Enums\Feature;
use App\Services\Menu\MenuFonts;
use Illuminate\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class MenuFontsTest extends TestCase
{
    use CreatesOwners;
    use RefreshDatabase;

    public function test_the_catalogue_is_config_fonts(): void
    {
        $this->assertSame(config('fonts'), MenuFonts::catalogue());
        $this->assertSame(['latin', 'arabic', 'cyrillic', 'chinese', 'devanagari'], array_keys(MenuFonts::catalogue()));
    }

    public function test_the_catalogue_is_empty_without_config(): void
    {
        $this->app->instance('config', new Repository(array_diff_key(config()->all(), ['fonts' => true])));

        $this->assertSame([], MenuFonts::catalogue());
        $this->assertSame([], MenuFonts::choices('latin'));
    }

    public function test_every_menu_language_names_a_script_the_catalogue_has(): void
    {
        foreach (array_keys(config('locales.menu')) as $locale) {
            $this->assertArrayHasKey(MenuFonts::scriptOf($locale), MenuFonts::catalogue(), "[{$locale}] has no fonts.");
        }
    }

    public function test_every_default_is_one_of_its_own_choices(): void
    {
        foreach (MenuFonts::catalogue() as $script => $entry) {
            $this->assertContains($entry['default'], MenuFonts::choices($script), "[{$script}]'s default is not offered.");
        }
    }

    public function test_the_script_of_each_language(): void
    {
        $this->assertSame('latin', MenuFonts::scriptOf('en'));
        $this->assertSame('latin', MenuFonts::scriptOf('fr'));
        $this->assertSame('arabic', MenuFonts::scriptOf('ar'));
        $this->assertSame('cyrillic', MenuFonts::scriptOf('ru'));
        $this->assertSame('chinese', MenuFonts::scriptOf('zh'));
        $this->assertSame('devanagari', MenuFonts::scriptOf('hi'));
        $this->assertSame('latin', MenuFonts::scriptOf('xx'), 'An unknown language is drawn in Latin.');
    }

    public function test_an_english_only_menu_uses_latin_alone(): void
    {
        $this->assertSame(['latin' => ['en']], MenuFonts::scripts($this->owner(['second_locale' => 'ar'])), 'Free has no second language.');
    }

    public function test_a_second_language_brings_its_script_after_latin(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $this->assertSame(['latin' => ['en'], 'arabic' => ['ar']], MenuFonts::scripts($this->owner(['second_locale' => 'ar'])));
        $this->assertSame(['latin' => ['en'], 'cyrillic' => ['ru']], MenuFonts::scripts($this->owner(['second_locale' => 'ru'])));
    }

    public function test_a_second_language_in_latin_shares_the_latin_script(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $this->assertSame(['latin' => ['en', 'fr']], MenuFonts::scripts($this->owner(['second_locale' => 'fr'])));
    }

    public function test_a_switched_off_language_takes_its_script_with_it(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $restaurant = $this->owner(['second_locale' => 'zh', 'switched_off' => ['languages']]);

        $this->assertSame(['latin' => ['en']], MenuFonts::scripts($restaurant));
        $this->assertSame(['Inter'], MenuFonts::allFamilies($restaurant));
    }

    public function test_the_choices_for_a_script(): void
    {
        $this->assertSame(['Noto Sans SC', 'Noto Serif SC', 'ZCOOL XiaoWei'], MenuFonts::choices('chinese'));
        $this->assertContains('Cairo', MenuFonts::choices('arabic'));
        $this->assertNotContains('Cairo', MenuFonts::choices('latin'));
        $this->assertSame([], MenuFonts::choices('klingon'));
    }

    public function test_without_appearance_the_menu_draws_in_the_defaults_and_keeps_the_pick(): void
    {
        $restaurant = $this->owner(['menu_fonts' => ['latin' => 'Lora', 'arabic' => 'Cairo']]);

        $this->assertSame('Inter', MenuFonts::family($restaurant, 'latin'));
        $this->assertSame('El Messiri', MenuFonts::family($restaurant, 'arabic'));
        $this->assertSame('Lora', MenuFonts::chosen($restaurant, 'latin'), 'The dashboard still shows the pick.');
        $this->assertSame('Cairo', MenuFonts::chosen($restaurant, 'arabic'));
    }

    public function test_with_appearance_the_menu_draws_in_the_owners_pick(): void
    {
        $this->defaultPackageIncludes(Feature::Appearance);

        $restaurant = $this->owner(['menu_fonts' => ['latin' => 'Lora', 'arabic' => 'Cairo']]);

        $this->assertSame('Lora', MenuFonts::family($restaurant, 'latin'));
        $this->assertSame('Cairo', MenuFonts::family($restaurant, 'arabic'));
        $this->assertSame('Noto Sans SC', MenuFonts::family($restaurant, 'chinese'), 'No pick for Chinese: its default.');
    }

    public function test_a_pick_that_is_not_offered_for_its_script_reads_as_the_default(): void
    {
        $this->defaultPackageIncludes(Feature::Appearance);

        $restaurant = $this->owner(['menu_fonts' => [
            'latin' => 'Cairo',
            'arabic' => 'Comic Sans',
            'chinese' => 42,
            'devanagari' => ['Hind'],
        ]]);

        $this->assertSame('Inter', MenuFonts::chosen($restaurant, 'latin'), 'An Arabic font is not a Latin pick.');
        $this->assertSame('El Messiri', MenuFonts::chosen($restaurant, 'arabic'));
        $this->assertSame('Noto Sans SC', MenuFonts::chosen($restaurant, 'chinese'));
        $this->assertSame('Noto Sans Devanagari', MenuFonts::chosen($restaurant, 'devanagari'));
    }

    public function test_no_fonts_saved_at_all_reads_as_the_defaults(): void
    {
        $restaurant = $this->owner(['menu_fonts' => null]);

        $this->assertSame('Inter', MenuFonts::chosen($restaurant, 'latin'));
        $this->assertSame('Inter', MenuFonts::chosen($restaurant, 'cyrillic'));
        $this->assertSame('Inter', MenuFonts::chosen($restaurant, 'klingon'), 'A script the catalogue lacks falls back to Inter.');
    }

    public function test_a_latin_language_is_drawn_in_the_latin_font_alone(): void
    {
        $this->defaultPackageIncludes(Feature::Appearance);

        $restaurant = $this->owner(['menu_fonts' => ['latin' => 'Poppins']]);

        $this->assertSame(['Poppins'], MenuFonts::stack($restaurant, 'en'));
        $this->assertSame(['Poppins'], MenuFonts::stack($restaurant, 'fr'));
    }

    public function test_arabic_and_chinese_put_the_latin_pick_first(): void
    {
        $this->defaultPackageIncludes(Feature::Appearance);

        $restaurant = $this->owner(['menu_fonts' => ['latin' => 'Lora', 'arabic' => 'Amiri', 'chinese' => 'Noto Serif SC']]);

        $this->assertSame(['Lora', 'Amiri'], MenuFonts::stack($restaurant, 'ar'));
        $this->assertSame(['Lora', 'Noto Serif SC'], MenuFonts::stack($restaurant, 'zh'));
    }

    public function test_cyrillic_and_devanagari_put_their_own_pick_first(): void
    {
        $this->defaultPackageIncludes(Feature::Appearance);

        $restaurant = $this->owner(['menu_fonts' => ['latin' => 'Lora', 'cyrillic' => 'PT Sans', 'devanagari' => 'Hind']]);

        $this->assertSame(['PT Sans', 'Lora'], MenuFonts::stack($restaurant, 'ru'));
        $this->assertSame(['Hind', 'Lora'], MenuFonts::stack($restaurant, 'hi'));
    }

    public function test_the_same_family_for_both_is_listed_once(): void
    {
        $this->defaultPackageIncludes(Feature::Appearance);

        $this->assertSame(['Inter'], MenuFonts::stack($this->owner(), 'ru'), 'Both defaults are Inter.');
        $this->assertSame(['Poppins'], MenuFonts::stack($this->owner(['menu_fonts' => ['latin' => 'Poppins', 'devanagari' => 'Poppins']]), 'hi'));
    }

    public function test_the_default_stack_without_appearance(): void
    {
        $restaurant = $this->owner(['menu_fonts' => ['latin' => 'Lora', 'arabic' => 'Amiri']]);

        $this->assertSame(['Inter', 'El Messiri'], MenuFonts::stack($restaurant, 'ar'));
        $this->assertSame(['Noto Sans Devanagari', 'Inter'], MenuFonts::stack($restaurant, 'hi'));
    }

    /** A script missing from the catalogue stacks Latin first, since `latin_first` defaults on. */
    public function test_a_script_the_catalogue_lacks_stacks_latin_first_then_inter(): void
    {
        config()->set('locales.menu.tlh', ['name' => 'tlhIngan', 'english' => 'Klingon', 'flag' => '', 'rtl' => false, 'script' => 'klingon']);
        $this->defaultPackageIncludes(Feature::Appearance);

        $this->assertSame(['Lora', 'Inter'], MenuFonts::stack($this->owner(['menu_fonts' => ['latin' => 'Lora']]), 'tlh'));
    }

    public function test_every_family_the_menu_uses_once_each(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages, Feature::Appearance);

        $this->assertSame(['Lora', 'Cairo'], MenuFonts::allFamilies($this->owner([
            'second_locale' => 'ar',
            'menu_fonts' => ['latin' => 'Lora', 'arabic' => 'Cairo', 'chinese' => 'ZCOOL XiaoWei'],
        ])));

        $this->assertSame(['Inter'], MenuFonts::allFamilies($this->owner(['second_locale' => 'ru'])), 'Latin and Cyrillic both default to Inter.');
    }

    public function test_css_quotes_each_family_in_order(): void
    {
        $this->assertSame("'Lora', 'IBM Plex Sans Arabic'", MenuFonts::css(['Lora', 'IBM Plex Sans Arabic']));
        $this->assertSame("'Inter'", MenuFonts::css(['Inter']));
        $this->assertSame('', MenuFonts::css([]));
    }

    public function test_the_google_fonts_url_asks_only_for_the_weights_each_family_has(): void
    {
        $this->assertSame(
            'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Almarai:wght@400;700&family=ZCOOL+XiaoWei:wght@400&display=swap',
            MenuFonts::href(['Inter', 'Almarai', 'ZCOOL XiaoWei']),
        );
    }

    public function test_a_family_with_spaces_is_joined_with_plus_signs(): void
    {
        $this->assertSame(
            'https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari:wght@400;500;600;700&display=swap',
            MenuFonts::href(['Noto Sans Devanagari']),
        );
    }

    public function test_a_family_outside_the_catalogue_is_asked_for_at_400_only(): void
    {
        $this->assertSame(
            'https://fonts.googleapis.com/css2?family=Comic+Sans+MS:wght@400&display=swap',
            MenuFonts::href(['Comic Sans MS']),
        );
    }

    public function test_the_url_for_the_menus_own_families(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages, Feature::Appearance);

        $restaurant = $this->owner(['second_locale' => 'ar', 'menu_fonts' => ['latin' => 'Lora', 'arabic' => 'Tajawal']]);

        $this->assertSame(
            'https://fonts.googleapis.com/css2?family=Lora:wght@400;500;600;700&family=Tajawal:wght@400;500;700&display=swap',
            MenuFonts::href(MenuFonts::allFamilies($restaurant)),
        );
    }
}
