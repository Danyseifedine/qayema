<?php

namespace Tests\Feature\Api;

use App\Models\Restaurant;
use App\Services\Menu\MenuCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What a restaurant pays a month and whether through Whish, from the admin
 * app (`PUT /api/admin/restaurants/{id}/billing`).
 */
class AdminRestaurantBillingTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->restaurant = $this->ownerOn('premium');
        $this->withToken($this->admin()->createToken('phone')->plainTextToken);
    }

    private function url(?int $id = null): string
    {
        return '/api/admin/restaurants/'.($id ?? $this->restaurant->id).'/billing';
    }

    public function test_it_sets_its_own_price_and_whish(): void
    {
        $this->putJson($this->url(), ['price_cents' => 1250, 'pays_with_whish' => true])
            ->assertOk()
            ->assertJsonPath('data.id', $this->restaurant->id)
            ->assertJsonPath('data.package.price_cents', 1250)
            ->assertJsonPath('data.package.monthly_price_cents', 1250)
            ->assertJsonPath('data.package.currency', 'USD')
            ->assertJsonPath('data.pays_with_whish', true)
            // The restaurant's whole page, as every change answers.
            ->assertJsonStructure(['data' => ['stats' => ['writing_help', 'ai_credits']]]);

        $restaurant = $this->restaurant->fresh();
        $this->assertSame(1250, $restaurant->price_cents);
        $this->assertTrue($restaurant->pays_with_whish);
        // A new price is a row in the package history, on the same package.
        $change = $restaurant->packageChanges()->latest('id')->first();
        $this->assertSame(1250, $change->price_cents);
        $this->assertSame($restaurant->package_id, $change->to_package_id);
    }

    public function test_no_price_is_the_packages_price(): void
    {
        $this->restaurant->update(['price_cents' => 500, 'pays_with_whish' => true]);

        $this->putJson($this->url(), ['price_cents' => null, 'pays_with_whish' => false])
            ->assertOk()
            ->assertJsonPath('data.package.price_cents', null)
            ->assertJsonPath('data.package.monthly_price_cents', 1000)
            ->assertJsonPath('data.pays_with_whish', false);

        // Left out is the same as null.
        $this->restaurant->update(['price_cents' => 500]);
        $this->putJson($this->url(), ['pays_with_whish' => false])
            ->assertJsonPath('data.package.price_cents', null);
    }

    public function test_a_price_of_nothing_is_kept_apart_from_no_price(): void
    {
        $this->putJson($this->url(), ['price_cents' => 0, 'pays_with_whish' => false])
            ->assertJsonPath('data.package.price_cents', 0)
            ->assertJsonPath('data.package.monthly_price_cents', 0);

        $this->assertSame(0, $this->restaurant->fresh()->price_cents);
    }

    public function test_it_leaves_the_guests_menu_alone(): void
    {
        $this->mock(MenuCache::class)->shouldNotReceive('forget');

        $this->putJson($this->url(), ['price_cents' => 600, 'pays_with_whish' => true])->assertOk();
    }

    public function test_it_moves_the_monthly_income(): void
    {
        $this->putJson($this->url(), ['price_cents' => 2000, 'pays_with_whish' => true])->assertOk();

        $this->getJson('/api/admin/summary')
            ->assertJsonPath('data.income', [
                'paying' => 1,
                'paying_whish' => 1,
                'totals' => [['currency' => 'USD', 'cents' => 2000, 'whish_cents' => 2000]],
            ]);
    }

    public function test_a_bad_price_or_whish_is_refused(): void
    {
        foreach ([-1, 10_000_001, 'ten', 12.5] as $price) {
            $this->putJson($this->url(), ['price_cents' => $price, 'pays_with_whish' => false])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['price_cents']);
        }

        $this->putJson($this->url(), ['price_cents' => 10_000_000])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pays_with_whish']);
        $this->putJson($this->url(), ['price_cents' => 500, 'pays_with_whish' => 'maybe'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['pays_with_whish']);

        $this->assertNull($this->restaurant->fresh()->price_cents);
    }

    public function test_the_highest_price_is_taken(): void
    {
        $this->putJson($this->url(), ['price_cents' => 10_000_000, 'pays_with_whish' => false])
            ->assertOk()
            ->assertJsonPath('data.package.price_cents', 10_000_000);
    }

    public function test_an_unknown_restaurant_is_not_found(): void
    {
        $this->putJson($this->url($this->restaurant->id + 999), ['price_cents' => 500, 'pays_with_whish' => false])
            ->assertNotFound();
    }

    public function test_only_an_admin_reaches_it(): void
    {
        $this->app['auth']->forgetGuards();
        $owner = $this->owner()->user;

        $this->withToken($owner->createToken('phone')->plainTextToken)
            ->putJson($this->url(), ['price_cents' => 1, 'pays_with_whish' => true])
            ->assertForbidden();

        $this->assertNull($this->restaurant->fresh()->price_cents);
    }
}
