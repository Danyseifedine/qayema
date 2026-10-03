<?php

namespace Tests\Feature\Packages;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What each package flag opens, seen from the owner's API and the guest's
 * menu. Everything starts on Free, which has none of them, and moves to the
 * package that does. Nothing an owner chose is lost on the way down.
 */
class PackageGatesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function classic(): Template
    {
        return Template::factory()->withSettings(Template::CLASSIC_SCHEMA)->create(['slug' => 'classic']);
    }

    private function menu(string $slug, string $query = ''): string
    {
        return $this->get('/'.$slug.$query)->assertOk()->getContent();
    }

    public function test_basic_analytics_need_the_analytics_flag(): void
    {
        $free = $this->owner();

        $this->actingAs($free->user)->getJson(route('api.analytics'))->assertForbidden();
    }

    public function test_pro_reads_its_analytics(): void
    {
        $pro = $this->ownerOn('pro');

        $this->actingAs($pro->user)->getJson(route('api.analytics'))->assertOk();
        $this->actingAs($pro->user)->getJson(route('api.analytics.advanced'))->assertForbidden();
    }

    public function test_every_package_sees_the_teaser_number(): void
    {
        $free = $this->owner();
        $free->menuSessions()->create(['session_id' => 'a', 'viewed_at' => now()->subDays(2)]);
        $free->menuSessions()->create(['session_id' => 'b', 'viewed_at' => now()->subDays(20)]);

        $this->actingAs($free->user)->getJson(route('api.analytics.teaser'))
            ->assertOk()
            ->assertExactJson(['data' => ['views' => 1]]);
    }

    public function test_qr_scan_counts_follow_analytics_not_the_studio(): void
    {
        $free = $this->owner();
        $this->actingAs($free->user)->getJson(route('api.qr.show'))->assertJsonPath('data.stats', null);
    }

    public function test_pro_sees_scan_counts_on_a_plain_code(): void
    {
        $pro = $this->ownerOn('pro');

        $this->actingAs($pro->user)->getJson(route('api.qr.show'))
            ->assertJsonPath('data.unlocked', false)
            ->assertJsonPath('data.stats.total', 0);
    }

    public function test_appearance_is_saved_only_with_the_flag(): void
    {
        $classic = $this->classic();
        $free = $this->published(['template_id' => $classic->id]);

        $this->actingAs($free->user)->getJson(route('api.appearance.show'))->assertOk();
        $this->actingAs($free->user)->putJson(route('api.appearance.update'), ['settings' => ['primary_color' => '#1F6FEB']])
            ->assertForbidden();
    }

    public function test_without_appearance_the_menu_draws_the_design_defaults_and_keeps_the_choices(): void
    {
        $classic = $this->classic();
        $restaurant = $this->published([
            'slug' => 'olive',
            'template_id' => $classic->id,
            'template_settings' => [$classic->id => ['primary_color' => '#1F6FEB', 'show_name' => false]],
            'menu_fonts' => ['latin' => 'Poppins'],
        ]);

        $html = $this->menu('olive');
        $this->assertStringContainsString('--accent: '.Template::DEFAULT_PRIMARY_COLOR, $html);
        $this->assertStringContainsString('class="brand-name"', $html);
        $this->assertStringContainsString("--font: 'Inter'", $html);

        // The owner still sees what they chose, ready for an upgrade.
        $this->actingAs($restaurant->user)->getJson(route('api.appearance.show'))
            ->assertJsonPath('data.settings.0.value', '#1F6FEB')
            ->assertJsonPath('data.fonts.0.value', 'Poppins');

        $this->assignPackage($restaurant, 'pro');

        $html = $this->menu('olive');
        $this->assertStringContainsString('--accent: #1F6FEB', $html);
        $this->assertStringNotContainsString('class="brand-name"', $html);
        $this->assertStringContainsString("--font: 'Poppins'", $html);
    }

    public function test_without_a_second_language_the_menu_is_english_only_and_the_choice_is_kept(): void
    {
        $restaurant = $this->published(['slug' => 'olive', 'second_locale' => 'ar', 'default_locale' => 'ar']);

        $this->assertSame(['en'], $restaurant->menuLanguages());
        $this->assertStringContainsString('<html lang="en" dir="ltr">', $this->menu('olive', '?lang=ar'));
        $this->actingAs($restaurant->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.languages', ['en'])
            ->assertJsonPath('data.restaurant.second_locale', 'ar');

        $this->assignPackage($restaurant, 'pro');

        $this->assertSame(['en', 'ar'], $restaurant->fresh()->menuLanguages());
        $this->assertStringContainsString('<html lang="ar" dir="rtl">', $this->menu('olive'));
    }

    public function test_choosing_a_second_language_needs_the_flag_but_english_only_never_does(): void
    {
        $free = $this->owner();

        $this->actingAs($free->user)->putJson(route('api.menu-languages.update'), ['second_locale' => 'ar', 'default_locale' => 'en'])
            ->assertForbidden();
        $this->actingAs($free->user)->putJson(route('api.menu-languages.update'), ['second_locale' => null, 'default_locale' => 'en'])
            ->assertOk();
    }

    public function test_a_premium_design_is_locked_below_premium(): void
    {
        $this->classic();
        $midnight = Template::factory()->create(['slug' => 'midnight', 'is_premium' => true, 'sort_order' => 1]);
        $pro = $this->ownerOn('pro');

        $this->actingAs($pro->user)->getJson(route('api.templates.index'))
            ->assertJsonPath('data.1.is_premium', true)
            ->assertJsonPath('data.1.locked', true)
            ->assertJsonPath('data.0.locked', false);
        $this->actingAs($pro->user)->postJson(route('api.templates.select'), ['template_id' => $midnight->id])
            ->assertForbidden();
    }

    public function test_after_a_downgrade_the_menu_shows_the_fallback_design_and_the_choice_comes_back(): void
    {
        $classic = $this->classic();
        $midnight = Template::factory()
            ->withSettings([['key' => 'primary_color', 'type' => 'color', 'default' => '#1F6FEB']])
            ->create(['slug' => 'midnight', 'is_premium' => true, 'sort_order' => 1]);
        $restaurant = $this->published(['slug' => 'olive', 'template_id' => $midnight->id]);

        $this->assertSame($classic->id, $restaurant->menuTemplate()->id);
        $this->assertStringContainsString('--accent: '.Template::DEFAULT_PRIMARY_COLOR, $this->menu('olive'));
        $this->actingAs($restaurant->user)->getJson(route('api.templates.index'))
            ->assertJsonPath('meta.current', $midnight->id)
            ->assertJsonPath('meta.shown', $classic->id);

        $this->assignPackage($restaurant, 'premium');

        $this->assertStringContainsString('--accent: #1F6FEB', $this->menu('olive'));
        $this->assertSame($midnight->id, $restaurant->fresh()->template_id, 'The choice was never overwritten.');
    }

    public function test_the_session_plan_lists_every_flag(): void
    {
        $premium = $this->ownerOn('premium');

        $this->actingAs($premium->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.plan', [
                'multiple_languages' => true,
                'variants' => true,
                'addons' => true,
                'appearance' => true,
                'premium_designs' => true,
                'qr_studio' => true,
                'ordering' => true,
                'menu_ordering' => true,
                'analytics' => true,
                'advanced_analytics' => true,
            ]);
    }

    private function assignPackage(\App\Models\Restaurant $restaurant, string $slug): void
    {
        app(\App\Services\Packages\PackageAssigner::class)->assign($restaurant, \App\Models\Package::findBySlug($slug));
    }
}
