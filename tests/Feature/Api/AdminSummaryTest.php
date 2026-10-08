<?php

namespace Tests\Feature\Api;

use App\Models\MenuSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The admin app's home (`GET /api/admin/summary`).
 */
class AdminSummaryTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_it_counts_restaurants_by_what_needs_attention(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $this->owner(['created_at' => now()->subMonth()]);
        $this->owner(['created_at' => now()->subDays(2)]);
        $this->owner(['created_at' => now()->subMonth(), 'is_active' => false]);
        $this->ownerOn('pro', ['created_at' => now()->subMonth(), 'package_ends_at' => now()->addDays(3)]);
        $this->ownerOn('pro', ['created_at' => now()->subMonth(), 'package_ends_at' => now()->addDays(20)]);
        $this->ownerOn('pro', ['created_at' => now()->subMonth(), 'package_ends_at' => now()->subDay()]);
        $visited = $this->owner(['created_at' => now()->subMonth()]);
        MenuSession::create(['restaurant_id' => $visited->id, 'session_id' => 'a', 'viewed_at' => now()->subMinutes(5)]);
        MenuSession::create(['restaurant_id' => $visited->id, 'session_id' => 'b', 'viewed_at' => now()->subDays(2)]);

        $this->withToken($this->admin()->createToken('phone')->plainTextToken);
        $this->getJson('/api/admin/summary')
            ->assertOk()
            ->assertExactJson(['data' => [
                'restaurants' => 7,
                'new_this_week' => 1,
                'menus_off' => 1,
                'ending_soon' => 1,
                'ended' => 1,
                'visits_today' => 1,
            ]]);
    }

    public function test_an_empty_platform_is_all_zeros(): void
    {
        $this->withToken($this->admin()->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/summary')
            ->assertExactJson(['data' => [
                'restaurants' => 0, 'new_this_week' => 0, 'menus_off' => 0,
                'ending_soon' => 0, 'ended' => 0, 'visits_today' => 0,
            ]]);
    }

    public function test_new_this_week_has_its_own_list(): void
    {
        $this->owner(['created_at' => now()->subMonth()]);
        $new = $this->owner(['created_at' => now()->subDay()]);

        $this->withToken($this->admin()->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/restaurants?filter=new')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $new->id);
    }

    public function test_only_admins_see_it(): void
    {
        $this->withToken($this->owner()->user->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/summary')
            ->assertForbidden();
    }
}
