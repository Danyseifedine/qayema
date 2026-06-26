<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\Template;
use App\Services\Global\Entitlements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_limits_fall_back_to_config_defaults_without_a_template(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertSame(40, $restaurant->dish_limit);
        $this->assertSame(10, $restaurant->category_limit);
        $this->assertSame(4, $restaurant->social_link_limit);
        $this->assertFalse($restaurant->entitlements()->can('ordering'));
    }

    public function test_free_template_bundle_provides_entitlements(): void
    {
        $template = Template::factory()->create(['tier' => 'free']);
        $dishLimit = Feature::factory()->limit()->create(['slug' => 'dish_limit']);
        $template->features()->attach($dishLimit->id, ['value' => '100']);

        $restaurant = Restaurant::factory()->create(['template_id' => $template->id]);

        $this->assertSame(100, $restaurant->dish_limit);
    }

    public function test_paid_template_without_subscription_grants_nothing_and_menu_is_offline(): void
    {
        $template = Template::factory()->paid()->create();
        $dishLimit = Feature::factory()->limit()->create(['slug' => 'dish_limit']);
        $template->features()->attach($dishLimit->id, ['value' => '500']);

        $restaurant = Restaurant::factory()->create(['template_id' => $template->id]);

        $this->assertFalse($restaurant->menuIsPubliclyAvailable());
        $this->assertSame(40, $restaurant->dish_limit, 'Falls back to the config default, not the paid bundle.');
    }

    public function test_paid_template_with_active_subscription_grants_bundle_and_menu_is_online(): void
    {
        $template = Template::factory()->paid()->create();
        $dishLimit = Feature::factory()->limit()->create(['slug' => 'dish_limit']);
        $template->features()->attach($dishLimit->id, ['value' => '500']);

        $restaurant = Restaurant::factory()->create(['template_id' => $template->id]);

        Subscription::factory()->create([
            'restaurant_id' => $restaurant->id,
            'subscribable_type' => Template::class,
            'subscribable_id' => $template->id,
        ]);

        $this->assertTrue($restaurant->fresh()->menuIsPubliclyAvailable());
        $this->assertSame(500, $restaurant->fresh()->dish_limit);
    }

    public function test_past_due_subscription_keeps_entitling_within_grace(): void
    {
        $template = Template::factory()->paid()->create();
        $restaurant = Restaurant::factory()->create(['template_id' => $template->id]);

        Subscription::factory()->pastDue()->create([
            'restaurant_id' => $restaurant->id,
            'subscribable_type' => Template::class,
            'subscribable_id' => $template->id,
        ]);

        $this->assertTrue($restaurant->menuIsPubliclyAvailable());
    }

    public function test_expired_subscription_takes_menu_offline(): void
    {
        $template = Template::factory()->paid()->create();
        $restaurant = Restaurant::factory()->create(['template_id' => $template->id]);

        Subscription::factory()->expired()->create([
            'restaurant_id' => $restaurant->id,
            'subscribable_type' => Template::class,
            'subscribable_id' => $template->id,
        ]);

        $this->assertFalse($restaurant->menuIsPubliclyAvailable());
    }

    public function test_feature_grant_overlays_and_merges_with_max(): void
    {
        $dishLimit = Feature::factory()->limit()->create(['slug' => 'dish_limit']);
        $ordering = Feature::factory()->addon()->create(['slug' => 'ordering']);
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $restaurant->featureGrants()->create([
            'feature_id' => $dishLimit->id,
            'value' => '90',
            'source' => 'grant',
            'starts_at' => now()->subDay(),
        ]);
        $restaurant->featureGrants()->create([
            'feature_id' => $ordering->id,
            'value' => '1',
            'source' => 'grant',
            'starts_at' => now()->subDay(),
        ]);

        Entitlements::flush($restaurant->id);

        $this->assertSame(90, $restaurant->dish_limit);
        $this->assertTrue($restaurant->entitlements()->can('ordering'));
    }

    public function test_expired_grant_does_not_apply(): void
    {
        $ordering = Feature::factory()->addon()->create(['slug' => 'ordering']);
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $restaurant->featureGrants()->create([
            'feature_id' => $ordering->id,
            'value' => '1',
            'source' => 'grant',
            'starts_at' => now()->subMonths(2),
            'ends_at' => now()->subMonth(),
        ]);

        Entitlements::flush($restaurant->id);

        $this->assertFalse($restaurant->entitlements()->can('ordering'));
    }
}
