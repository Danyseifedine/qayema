<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\PackageDefault;
use App\Models\Restaurant;
use App\Models\Template;
use App\Services\Global\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_limits_fall_back_to_db_defaults_without_a_package(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        // The seeded package_defaults rows (migration) are the floor.
        $this->assertSame(40, $restaurant->dish_limit);
        $this->assertSame(10, $restaurant->category_limit);
        $this->assertSame(4, $restaurant->social_link_limit);
        $this->assertFalse($restaurant->package()->can('ordering'));
    }

    public function test_editing_a_db_default_changes_the_floor_limit(): void
    {
        PackageDefault::set('category_limit', 25);

        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertSame(25, $restaurant->category_limit);
        $this->assertSame(40, $restaurant->dish_limit, 'Untouched defaults keep their seeded value.');
    }

    public function test_creating_a_restaurant_snapshots_the_default_limits_as_grants(): void
    {
        Feature::factory()->limit()->create(['slug' => 'dish_limit']);
        Feature::factory()->limit()->create(['slug' => 'category_limit']);
        Feature::factory()->limit()->create(['slug' => 'social_link_limit']);
        PackageDefault::set('category_limit', 12);

        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertSame(3, $restaurant->featureGrants()->count());
        $this->assertDatabaseHas('restaurant_features', [
            'restaurant_id' => $restaurant->id,
            'source' => 'default',
            'value' => '12',
        ]);
        $this->assertSame(12, $restaurant->category_limit);
    }

    public function test_free_template_bundle_provides_package(): void
    {
        $template = Template::factory()->create(['tier' => 'free']);
        $dishLimit = Feature::factory()->limit()->create(['slug' => 'dish_limit']);
        $template->features()->attach($dishLimit->id, ['value' => '100']);

        $restaurant = Restaurant::factory()->create(['template_id' => $template->id]);

        $this->assertSame(100, $restaurant->dish_limit);
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

        Package::flush($restaurant->id);

        $this->assertSame(90, $restaurant->dish_limit);
        $this->assertTrue($restaurant->package()->can('ordering'));
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

        Package::flush($restaurant->id);

        $this->assertFalse($restaurant->package()->can('ordering'));
    }
}
