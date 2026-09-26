<?php

namespace Tests\Feature\Menu;

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

        foreach (config('locales.supported') as $code) {
            $this->assertStringContainsString('hreflang="'.$code.'"', $html);
            $this->assertStringContainsString($base.'?lang='.$code, $html);
        }

        $this->assertStringContainsString('hreflang="x-default"', $html);

        // The switcher marks where the guest already is.
        $this->assertStringContainsString('class="pop-item"', $html);
        $this->assertStringContainsString('lang="en"', $html);
        $this->assertStringContainsString('aria-current="true"', $html);
    }

    public function test_a_language_switch_is_not_counted_as_a_second_visit(): void
    {
        $restaurant = $this->shop('en');
        $base = route('public.menu', $restaurant->slug);

        $this->get($base)->assertOk();
        $this->assertSame(1, $restaurant->statistics()->count());

        // Arriving from the menu itself is the same visit continuing.
        $this->get($base.'?lang=ar', ['referer' => $base])->assertOk();
        $this->assertSame(1, $restaurant->statistics()->count());

        // Someone opening a shared Arabic link is a new visit.
        $this->get($base.'?lang=ar')->assertOk();
        $this->assertSame(2, $restaurant->statistics()->count());
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
