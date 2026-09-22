<?php

namespace Tests\Unit\Models;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestaurantSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_slug_is_derived_from_the_name_when_none_is_given(): void
    {
        $restaurant = Restaurant::factory()->create(['slug' => null, 'name' => ['en' => "Joe's Diner"]]);

        $this->assertSame('joes-diner', $restaurant->slug);
    }

    public function test_collisions_get_a_numeric_suffix(): void
    {
        Restaurant::factory()->create(['slug' => 'joes-diner']);
        $second = Restaurant::factory()->create(['slug' => null, 'name' => ['en' => "Joe's Diner"]]);
        $third = Restaurant::factory()->create(['slug' => null, 'name' => ['en' => "Joe's Diner"]]);

        $this->assertSame('joes-diner-2', $second->slug);
        $this->assertSame('joes-diner-3', $third->slug);
    }

    public function test_a_name_that_slugifies_to_nothing_falls_back_to_menu(): void
    {
        $restaurant = Restaurant::factory()->create(['slug' => null, 'name' => ['ar' => 'مطعم'], 'default_locale' => 'ar']);

        $this->assertSame('menu', $restaurant->slug);
    }

    public function test_an_explicit_slug_is_kept_as_is(): void
    {
        $restaurant = Restaurant::factory()->create(['slug' => 'chosen-by-owner', 'name' => ['en' => 'Whatever']]);

        $this->assertSame('chosen-by-owner', $restaurant->slug);
    }
}
