<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * A guest reads the menu in the owner's language by default and can switch to
 * any other supported one with ?lang=. The URL carries it rather than the
 * session, so each version is shareable and indexable on its own.
 */
class MenuLocaleTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
    }

    private function shop(string $default = 'en'): Restaurant
    {
        $restaurant = $this->published([
            'slug' => 'olive',
            'default_locale' => $default,
            'google_maps_url' => 'https://maps.google.com/?q=33,35',
        ]);

        $category = Category::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => ['en' => 'Plates', 'ar' => 'أطباق'],
        ]);

        Dish::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => ['en' => 'House Bowl', 'ar' => 'صحن البيت'],
            'price' => '14.00',
        ]);

        return $restaurant;
    }

    public function test_the_menu_uses_the_owners_language_by_default(): void
    {
        $this->get(route('public.menu', $this->shop('en')->slug))
            ->assertOk()
            ->assertSee('House Bowl')
            ->assertSee('Search the menu')
            ->assertSee('dir="ltr"', false);
    }

    public function test_a_guest_can_ask_for_another_language(): void
    {
        $this->get(route('public.menu', $this->shop('en')->slug).'?lang=ar')
            ->assertOk()
            // Both the content and the interface follow.
            ->assertSee('صحن البيت')
            ->assertSee('أطباق')
            ->assertSee('ابحث في القائمة')
            ->assertSee('dir="rtl"', false);
    }

    public function test_an_unsupported_language_falls_back_instead_of_erroring(): void
    {
        $restaurant = $this->shop('en');

        foreach (['fr', '', 'en-US', '../etc', '1'] as $asked) {
            $this->get(route('public.menu', $restaurant->slug).'?lang='.urlencode($asked))
                ->assertOk()
                ->assertSee('House Bowl');
        }
    }

    public function test_every_language_is_offered_and_declared(): void
    {
        $restaurant = $this->shop('en');
        $base = route('public.menu', $restaurant->slug);

        $html = $this->get($base)->assertOk()->getContent();

        foreach ($restaurant->menuLanguages() as $code) {
            $this->assertStringContainsString('hreflang="'.$code.'"', $html);
            $this->assertStringContainsString($base.'?lang='.$code, $html);
        }

        $this->assertStringContainsString('hreflang="x-default"', $html);

        // The switcher marks where the guest already is.
        $this->assertStringContainsString('class="pop-item"', $html);
        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('aria-current="true"', $html);
    }

    public function test_a_french_menu_speaks_french_and_offers_only_its_own_languages(): void
    {
        $restaurant = $this->shop('fr');
        $restaurant->update(['second_locale' => 'fr']);
        Dish::query()->first()->setTranslation('name', 'fr', 'Bol maison')->save();

        $html = $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('<html lang="fr" dir="ltr">', false)
            ->assertSee('Bol maison')
            ->assertSee('Rechercher dans le menu')
            // The category has no French name, so it shows the English one.
            ->assertSee('Plates')
            ->getContent();

        $this->assertStringContainsString('hreflang="en"', $html);
        $this->assertStringContainsString('hreflang="fr"', $html);
        $this->assertStringNotContainsString('hreflang="ar"', $html, 'Arabic text is kept, but not offered.');
    }

    public function test_a_language_the_menu_is_not_written_in_opens_the_default_one(): void
    {
        $restaurant = $this->shop('fr');
        $restaurant->update(['second_locale' => 'fr']);

        // The dish still has Arabic text from before, but Arabic is not one of
        // this menu's languages any more.
        $this->get(route('public.menu', $restaurant->slug).'?lang=ar')
            ->assertOk()
            ->assertSee('<html lang="fr"', false)
            ->assertDontSee('صحن البيت');
    }

    public function test_an_english_only_menu_has_no_language_switcher(): void
    {
        $restaurant = $this->shop('en');
        $restaurant->update(['second_locale' => null]);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertDontSee('id="pop-lang"', false)
            ->assertDontSee('hreflang="ar"', false);
    }

    public function test_switching_menu_languages_off_serves_english_only(): void
    {
        $restaurant = $this->shop('ar');
        $restaurant->update(['switched_off' => ['languages']]);

        $this->get(route('public.menu', $restaurant->slug).'?lang=ar')
            ->assertOk()
            ->assertSee('<html lang="en"', false)
            ->assertSee('House Bowl')
            ->assertDontSee('id="pop-lang"', false);
    }

    public function test_text_missing_in_the_second_language_shows_in_english(): void
    {
        $restaurant = $this->shop('ar');
        Dish::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => Category::query()->first()->id,
            'name' => ['en' => 'Lemonade'],
            'price' => '3.00',
        ]);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('صحن البيت')
            ->assertSee('Lemonade');
    }

    public function test_chinese_loads_a_font_that_has_its_characters(): void
    {
        $restaurant = $this->shop('zh');
        $restaurant->update(['second_locale' => 'zh']);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('family=Noto+Sans+SC', false)
            ->assertSee('搜索菜单')
            ->assertDontSee('El+Messiri', false);
    }

    public function test_a_language_switch_is_not_counted_as_a_second_visit(): void
    {
        $restaurant = $this->shop('en');
        $base = route('public.menu', $restaurant->slug);

        $this->get($base)->assertOk();
        $this->assertSame(1, $restaurant->menuSessions()->count());

        // Arriving from the menu itself is the same visit continuing.
        $this->get($base.'?lang=ar', ['referer' => $base])->assertOk();
        $this->assertSame(1, $restaurant->menuSessions()->count());

        // Someone opening a shared Arabic link is a new visit.
        $this->get($base.'?lang=ar')->assertOk();
        $this->assertSame(2, $restaurant->menuSessions()->count());
    }

    public function test_a_preview_keeps_previewing_when_the_language_changes(): void
    {
        $restaurant = $this->shop('en');

        $html = $this->actingAs($restaurant->user)
            ->get(route('public.menu', $restaurant->slug).'?preview='.$restaurant->template_id)
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('preview='.$restaurant->template_id, $html);
    }
}
