<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\Template;
use App\Support\Color;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A menu drawn in Ember, the premium dark design (its row comes from its
 * migration, Tests\Integration\Database\AddTheEmberDesignTest): every
 * hook the shared parts look for is kept, and the floating bar stands in
 * for Classic's dock.
 */
class EmberDesignTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function ember(): Template
    {
        return Template::query()->where('slug', 'ember')->firstOrFail();
    }

    /** A restaurant on Ember, with a category, a dish with a photo and one without. */
    private function emberMenu(array $attributes = []): Restaurant
    {
        $restaurant = $this->published(array_merge([
            'template_id' => $this->ember()->id,
            'name' => ['en' => 'Olive & Ember'],
            'default_locale' => 'en',
        ], $attributes));

        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Mezze'], 'icon' => 'salad']);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => ['en' => 'Hummus'], 'price' => '5.50', 'is_available' => true])
            ->addMedia(UploadedFile::fake()->image('hummus.jpg', 800, 600))->toMediaCollection('image');
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => ['en' => 'Tabbouleh'], 'price' => '6.50', 'is_available' => true]);

        return $restaurant;
    }

    private function menu(Restaurant $restaurant, string $query = ''): string
    {
        return (string) $this->get(route('public.menu', $restaurant->slug).$query)->assertOk()->getContent();
    }

    public function test_a_premium_menu_is_drawn_in_ember_with_every_hook_the_shared_parts_need(): void
    {
        $this->defaultPackageIncludes(Feature::PremiumDesigns, Feature::Ordering, Feature::MultipleLanguages);
        $restaurant = $this->emberMenu([
            'phone' => '70123456',
            'country_code' => 'LB',
            'google_maps_url' => 'https://maps.google.com/?q=33.89,35.50',
            'second_locale' => 'ar',
            'name' => ['en' => 'Olive & Ember', 'ar' => 'زيتون وجمر'],
            'opening_hours' => ['mon' => [['open' => '00:00', 'close' => '23:59']]],
        ]);

        $html = $this->menu($restaurant);

        $this->assertStringContainsString('css/menu-ember.css', $html);
        $this->assertStringNotContainsString('css/menu-classic.css', $html);
        foreach ([
            'data-menu-search', 'data-search-close', 'data-open-cart data-cart-toggle', 'data-pop-open="contact"', 'data-cart-count', 'data-cart-panel="desktop"',
            'data-pop-open="qr"', 'data-pop-open="lang"', 'data-pop-open="map"', 'data-pop-open="contact"',
            'data-photo-open', 'data-dish=', 'data-track="call"', 'data-hours-label', 'data-hours-value',
            'id="pop-photo"', 'id="pop-qr"', 'id="pop-map"', 'id="pop-contact"', 'id="pop-lang"', 'id="menu-no-results"',
            'class="tabs"', 'class="tabs-inner"', 'data-tab="all"', 'class="dock"', 'id="cart-sheet"', 'Menu by',
        ] as $hook) {
            $this->assertStringContainsString($hook, $html, $hook);
        }
        // The floating bar, in its order: WhatsApp, the categories, the
        // language, the map and the cart, icons named for screen readers, no
        // words; the cart is there, never in the pill on the cover, and the
        // pill's language button is for a wide screen only.
        preg_match('/<nav class="dock".*?<\/nav>/s', $html, $dock);
        preg_match_all('/class="dockface[^"]*"[^>]*aria-label="([^"]+)"/', $dock[0], $labels);
        $this->assertSame(['WhatsApp', 'Menu', 'Language', 'Location', 'Cart'], $labels[1]);
        $this->assertStringContainsString('class="pill-button on-wide" data-pop-open="lang"', $html);
        $this->assertStringNotContainsString('dock-label', $html);
        $this->assertStringContainsString('href="#menu-categories"', $html);
        // Headings are the owner's own font, Playfair Display until they pick
        // another; only the tiles' names add a serif, at one weight, in the
        // same request.
        $this->assertStringContainsString('fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700&amp;family=EB+Garamond:wght@400&amp;display=swap', $html);
        $this->assertStringContainsString("--font-tiles: 'EB Garamond', var(--font);", $html);
        $this->assertStringContainsString('<body class="has-dock is-spaced">', $html);
    }

    public function test_without_premium_designs_the_menu_falls_back_to_classic(): void
    {
        Template::factory()->withSettings(Template::CLASSIC_SCHEMA)->create(['slug' => 'classic']);
        $restaurant = $this->emberMenu();

        $html = $this->menu($restaurant);

        $this->assertStringContainsString('css/menu-classic.css', $html);
        $this->assertStringNotContainsString('css/menu-ember.css', $html);
    }

    public function test_its_colours_and_the_name_beside_the_logo_are_the_owners(): void
    {
        $this->defaultPackageIncludes(Feature::PremiumDesigns, Feature::Appearance);
        $restaurant = $this->emberMenu();

        $html = $this->menu($restaurant);
        $this->assertStringContainsString('--accent: #E4CA55;', $html);
        $this->assertStringContainsString('--page: #0F0F0F;', $html);
        $this->assertStringContainsString('--text: #F3EBDD;', $html);
        $this->assertStringContainsString('color-scheme: dark;', $html);
        $this->assertStringNotContainsString('data-tone="light"', $html);
        $this->assertStringContainsString('<span class="brand-name"><bdi>Olive &amp; Ember</bdi></span>', $html);

        $restaurant->saveDesignSettings($this->ember(), ['primary_color' => '#C0392B', 'background_color' => '#FAF7F2', 'text_color' => '#1A1A1A', 'show_name' => false]);
        $html = $this->menu($restaurant->fresh());

        $this->assertStringContainsString('--accent: #C0392B;', $html);
        $this->assertStringContainsString('--page: #FAF7F2;', $html);
        $this->assertStringContainsString('color-scheme: light;', $html);
        // A light page lightens the cover's haze and the cards (menu-ember.css).
        $this->assertStringContainsString('data-tone="light">', $html);

        // A pale gold on a white page is deepened until it stands out.
        $restaurant->saveDesignSettings($this->ember(), ['primary_color' => '#E4CA55', 'background_color' => '#FFFFFF', 'text_color' => '#1A1A1A']);
        $this->assertStringContainsString('--accent: '.Color::readableOn('#E4CA55', '#FFFFFF').';', $this->menu($restaurant->fresh()));
        $this->assertStringNotContainsString('--accent: #E4CA55;', $this->menu($restaurant->fresh()));
        $this->assertStringNotContainsString('class="brand-name"', $html);
        $this->assertStringContainsString('<a class="brand" href="#top" aria-label="Olive &amp; Ember">', $html);
    }

    public function test_the_default_pictures_stand_in_for_a_missing_cover_and_dish_photo(): void
    {
        $this->defaultPackageIncludes(Feature::PremiumDesigns);
        $html = $this->menu($this->emberMenu());

        $this->assertMatchesRegularExpression('/<img class="hero-photo hero-photo-default" src="[^"]*images\/menu\/default-banner\.webp/', $html);
        $this->assertMatchesRegularExpression('/<img class="dish-photo dish-photo-default" src="[^"]*images\/menu\/default-dish-image\.webp/', $html);
        // Only the dish with a photo opens it big.
        $this->assertSame(1, substr_count($html, 'data-photo-open'));
    }

    public function test_a_menu_without_whatsapp_a_map_or_orders_leaves_those_out_of_the_bar(): void
    {
        $this->defaultPackageIncludes(Feature::PremiumDesigns);
        $html = $this->menu($this->emberMenu(['phone' => null]));

        $this->assertStringNotContainsString('data-pop-open="map"', $html);
        $this->assertStringNotContainsString('data-open-cart', $html);
        $this->assertStringNotContainsString('data-cart-panel', $html);
        $this->assertStringNotContainsString('aria-label="Location"', $html);
        $this->assertStringNotContainsString('aria-label="Cart"', $html);
        $this->assertStringNotContainsString('aria-label="WhatsApp"', $html);
        $this->assertStringContainsString('aria-label="Menu"', $html);
        // One language: nothing to switch to, in the bar or the pill.
        $this->assertStringNotContainsString('data-pop-open="lang"', $html);
    }

    public function test_an_arabic_menu_reads_right_to_left_without_spaced_letters(): void
    {
        $this->defaultPackageIncludes(Feature::PremiumDesigns, Feature::MultipleLanguages);
        $restaurant = $this->emberMenu(['second_locale' => 'ar', 'name' => ['en' => 'Olive & Ember', 'ar' => 'زيتون وجمر']]);

        $html = $this->menu($restaurant, '?lang=ar');

        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $html);
        $this->assertStringContainsString('<body class="has-dock">', $html);
        // Arabic tiles keep the owner's font: the serif is not even asked for.
        $this->assertStringNotContainsString('EB Garamond', $html);
        $this->assertStringContainsString('aria-label="القائمة"', $html);
    }

    public function test_open_now_and_todays_hours_show_on_the_cover(): void
    {
        $this->defaultPackageIncludes(Feature::PremiumDesigns);
        $restaurant = $this->emberMenu(['opening_hours' => [
            'mon' => [['open' => '00:00', 'close' => '23:59']], 'tue' => [['open' => '00:00', 'close' => '23:59']],
            'wed' => [['open' => '00:00', 'close' => '23:59']], 'thu' => [['open' => '00:00', 'close' => '23:59']],
            'fri' => [['open' => '00:00', 'close' => '23:59']], 'sat' => [['open' => '00:00', 'close' => '23:59']],
            'sun' => [['open' => '00:00', 'close' => '23:59']],
        ]]);

        $html = $this->menu($restaurant);

        $this->assertStringContainsString('<span class="hours-label fact-open" data-hours-label>Open now</span>', $html);
        $this->assertStringContainsString('data-hours-value>00:00 - 23:59</bdi>', $html);
    }

    public function test_opened_from_a_tables_code_the_cover_names_the_table(): void
    {
        $this->defaultPackageIncludes(Feature::PremiumDesigns, Feature::MenuOrdering, Feature::DineIn);
        $restaurant = $this->emberMenu(['order_mode' => 'menu']);
        $table = DiningTable::factory()->for($restaurant)->create(['name' => 'Terrace 2']);

        $html = $this->menu($restaurant, '?table='.$table->code);

        $this->assertStringContainsString('<span>Your table: <bdi>Terrace 2</bdi></span>', $html);
        $this->assertStringContainsString('data-cart-panel="desktop"', $html);
    }
}
