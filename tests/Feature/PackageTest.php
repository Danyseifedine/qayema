<?php

namespace Tests\Feature;

use App\Enums\Feature;
use App\Models\FeatureDefault;
use App\Models\Restaurant;
use App\Models\RestaurantFeature;
use App\Services\Global\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageTest extends TestCase
{
    use RefreshDatabase;

    public function test_limits_fall_back_to_the_seeded_defaults(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertSame(40, $restaurant->dish_limit);
        $this->assertSame(10, $restaurant->category_limit);
        $this->assertSame(2, $restaurant->social_link_limit);
        $this->assertFalse($restaurant->package()->can(Feature::QrStudio));
    }

    public function test_editing_a_default_changes_the_limit_for_every_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        FeatureDefault::set(Feature::CategoryLimit, 25);
        Package::flush($restaurant->id);

        $this->assertSame(25, $restaurant->category_limit);
        $this->assertSame(40, $restaurant->dish_limit, 'Untouched defaults keep their seeded value.');
    }

    public function test_a_grant_stacks_on_top_of_the_default(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        RestaurantFeature::factory()
            ->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)
            ->create();

        $this->assertSame(90, $restaurant->dish_limit, '40 default + 50 granted.');
    }

    public function test_several_grants_of_the_same_feature_add_up(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        RestaurantFeature::factory()->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)->create(['reference' => 'first']);
        RestaurantFeature::factory()->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)->create(['reference' => 'second']);

        $this->assertSame(140, $restaurant->dish_limit, '40 default + 50 + 50.');
    }

    public function test_a_flag_grant_unlocks_the_feature(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertFalse($restaurant->package()->can(Feature::QrStudio));

        RestaurantFeature::factory()
            ->for($restaurant)
            ->forFeature(Feature::QrStudio)
            ->create();

        $this->assertTrue($restaurant->package()->can(Feature::QrStudio));
    }

    public function test_an_expired_grant_does_not_apply(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        RestaurantFeature::factory()
            ->for($restaurant)
            ->forFeature(Feature::QrStudio)
            ->expired()
            ->create();

        $this->assertFalse($restaurant->package()->can(Feature::QrStudio));
        $this->assertSame(40, $restaurant->dish_limit);
    }

    public function test_deleting_a_grant_takes_the_allowance_away_again(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $grant = RestaurantFeature::factory()
            ->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)
            ->create();

        $this->assertSame(90, $restaurant->dish_limit);

        $grant->delete();

        $this->assertSame(40, $restaurant->fresh()->dish_limit, 'The cache is flushed on delete.');
    }

    public function test_templates_grant_nothing(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->assertSame(40, $restaurant->dish_limit);
        $this->assertFalse($restaurant->package()->can(Feature::QrStudio));
    }
}
