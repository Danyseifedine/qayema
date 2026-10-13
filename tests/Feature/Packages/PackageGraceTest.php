<?php

namespace Tests\Feature\Packages;

use App\Models\Dish;
use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Packages\PackageAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * A package that ends is kept, never swapped for the default: the menu stays
 * online for the grace days (config/package.php), then everything a guest
 * opens is a 404 until an admin extends or changes the package
 * (Restaurant::menuOnline()).
 */
class PackageGraceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    /** A live Pro menu whose package ended this long ago. */
    private function endedAgo(string $slug, int $hours): Restaurant
    {
        return $this->published([
            'slug' => $slug,
            'package_id' => Package::findBySlug('pro')->id,
            'package_started_at' => now()->subYear(),
            'package_ends_at' => now()->subHours($hours),
        ]);
    }

    public function test_the_menu_stays_online_through_the_grace_days_and_goes_offline_after(): void
    {
        $restaurant = $this->endedAgo('olive', 1);

        $this->get(route('public.menu', 'olive'))->assertOk();

        $this->travel(3 * 24 - 2)->hours();
        $this->get(route('public.menu', 'olive'))->assertOk();
        $this->assertTrue($restaurant->fresh()->menuOnline());

        $this->travel(2)->hours();
        $this->get(route('public.menu', 'olive'))->assertNotFound();
        $this->assertFalse($restaurant->fresh()->menuOnline());
        $this->assertSame('pro', $restaurant->fresh()->effectivePackage()->slug, 'Offline, never downgraded.');
    }

    public function test_offline_closes_everything_a_guest_opens(): void
    {
        $this->endedAgo('olive', 4 * 24);

        $this->get(route('public.menu', 'olive'))->assertNotFound();
        $this->get(route('public.menu', 'olive').'?qr=1')->assertNotFound();
        $this->getJson(route('public.qr.options', 'olive'))->assertNotFound();
        $this->postJson(route('public.events', 'olive'), ['events' => [['type' => 'map']]])->assertNotFound();
    }

    public function test_the_owner_may_still_preview_an_offline_menu(): void
    {
        $restaurant = $this->endedAgo('olive', 4 * 24);

        $this->actingAs($restaurant->user)
            ->get(route('public.menu', 'olive').'?preview='.$restaurant->template_id)
            ->assertOk();
    }

    public function test_extending_or_changing_the_package_brings_the_menu_back_at_once(): void
    {
        $extended = $this->endedAgo('olive', 4 * 24);
        $changed = $this->endedAgo('cedar', 4 * 24);
        $reset = $this->endedAgo('pine', 4 * 24);

        app(PackageAssigner::class)->extend($extended, 1);
        app(PackageAssigner::class)->assign($changed, Package::findBySlug('premium'), now(), now()->addMonth());
        app(PackageAssigner::class)->reset($reset);

        $this->get(route('public.menu', 'olive'))->assertOk();
        $this->get(route('public.menu', 'cedar'))->assertOk();
        $this->get(route('public.menu', 'pine'))->assertOk();
    }

    public function test_the_admin_switch_still_turns_a_menu_off_by_itself(): void
    {
        $restaurant = $this->endedAgo('olive', 1);
        $restaurant->update(['is_active' => false]);

        $this->get(route('public.menu', 'olive'))->assertNotFound();
        $this->assertFalse($restaurant->fresh()->menuOnline());
    }

    public function test_a_package_that_never_ends_is_always_online(): void
    {
        $restaurant = $this->published(['slug' => 'olive']);

        $this->assertNull($restaurant->offlineAt());
        $this->assertTrue($restaurant->menuOnline());
    }

    public function test_an_old_link_of_an_offline_menu_leads_nowhere(): void
    {
        $restaurant = $this->endedAgo('olive', 4 * 24);
        $restaurant->update(['slug' => 'olive-tree']);

        $this->get('/olive')->assertNotFound();
    }

    public function test_the_sitemap_lists_only_menus_guests_can_open(): void
    {
        foreach ([$this->endedAgo('in-grace', 1), $this->endedAgo('offline', 4 * 24)] as $restaurant) {
            Dish::factory()->for($restaurant)->create(['is_available' => true]);
        }

        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee('/in-grace', false)
            ->assertDontSee('/offline<', false);
    }

    public function test_the_online_scope_agrees_with_the_model(): void
    {
        $online = [$this->endedAgo('in-grace', 1), $this->published(['slug' => 'forever'])];
        $offline = [$this->endedAgo('offline', 4 * 24), $this->published(['slug' => 'off', 'is_active' => false])];

        $ids = Restaurant::query()->menuOnline()->pluck('id')->all();

        foreach ($online as $restaurant) {
            $this->assertTrue($restaurant->menuOnline());
            $this->assertContains($restaurant->id, $ids);
        }
        foreach ($offline as $restaurant) {
            $this->assertFalse($restaurant->menuOnline());
            $this->assertNotContains($restaurant->id, $ids);
        }
    }

    public function test_the_grace_days_come_from_the_config(): void
    {
        config(['package.grace_days' => 0]);
        $restaurant = $this->endedAgo('olive', 1);

        $this->assertFalse($restaurant->menuOnline());
        $this->get(route('public.menu', 'olive'))->assertNotFound();
    }
}
