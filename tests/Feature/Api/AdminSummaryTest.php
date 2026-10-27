<?php

namespace Tests\Feature\Api;

use App\Models\MenuSession;
use App\Models\Package;
use App\Models\WritingHelpDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The admin app's home (`GET /api/admin/summary`).
 */
class AdminSummaryTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** The writing help's figures when nobody used it. */
    private const NO_AI = [
        'uses' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'restaurants' => 0, 'cost' => 0,
        'credits' => 0, 'translation_uses' => 0, 'translation_cost' => 0, 'description_uses' => 0, 'description_cost' => 0,
    ];

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
                'writing_help_today' => self::NO_AI,
                'writing_help_month' => self::NO_AI,
                // The two on Pro still in force; the ended one is not paying.
                'income' => ['paying' => 2, 'paying_whish' => 0, 'totals' => [['currency' => 'USD', 'cents' => 1598, 'whish_cents' => 0]]],
            ]]);
    }

    public function test_an_empty_platform_is_all_zeros(): void
    {
        $this->withToken($this->admin()->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/summary')
            ->assertExactJson(['data' => [
                'restaurants' => 0, 'new_this_week' => 0, 'menus_off' => 0,
                'ending_soon' => 0, 'ended' => 0, 'visits_today' => 0,
                'writing_help_today' => self::NO_AI,
                'writing_help_month' => self::NO_AI,
                'income' => ['paying' => 0, 'paying_whish' => 0, 'totals' => []],
            ]]);
    }

    public function test_it_says_what_the_writing_help_spent_today_in_lebanon(): void
    {
        $this->travelTo(Carbon::parse('2026-10-12 10:00', 'Asia/Beirut'));
        WritingHelpDay::factory()->for($this->owner())->create(['day' => '2026-10-12', 'uses' => 3, 'input_tokens' => 900, 'output_tokens' => 120]);
        WritingHelpDay::factory()->for($this->owner())->create(['day' => '2026-10-12', 'uses' => 1, 'input_tokens' => 300, 'output_tokens' => 40]);
        WritingHelpDay::factory()->for($this->owner())->create(['day' => '2026-10-11', 'uses' => 9, 'input_tokens' => 5000, 'output_tokens' => 500]);

        $this->withToken($this->admin()->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/summary')
            ->assertJsonPath('data.writing_help_today', [...self::NO_AI, 'uses' => 4, 'input_tokens' => 1200, 'output_tokens' => 160, 'restaurants' => 2]);
    }

    public function test_it_says_what_the_writing_help_spent_this_month_in_lebanon(): void
    {
        // 1 am in Beirut, still the 2nd in UTC: today and the month are Lebanon's.
        $this->travelTo(Carbon::parse('2026-10-03 01:00', 'Asia/Beirut'));
        $restaurant = $this->owner();
        WritingHelpDay::factory()->for($restaurant)->create([
            'day' => '2026-10-03', 'uses' => 2, 'input_tokens' => 300, 'output_tokens' => 30, 'cost' => '0.000100', 'credits' => 4,
            'translation_uses' => 2, 'translation_cost' => '0.000100', 'description_uses' => 0, 'description_cost' => '0',
        ]);
        WritingHelpDay::factory()->for($this->owner())->create([
            'day' => '2026-10-01', 'uses' => 3, 'input_tokens' => 700, 'output_tokens' => 90, 'cost' => '0.000250', 'credits' => 9,
            'translation_uses' => 1, 'translation_cost' => '0.000050', 'description_uses' => 2, 'description_cost' => '0.000200',
        ]);
        // Last month.
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-09-30', 'uses' => 40, 'input_tokens' => 9000, 'output_tokens' => 900, 'cost' => '0.010000', 'credits' => 80]);

        $this->withToken($this->admin()->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/summary')
            ->assertJsonPath('data.writing_help_today', [
                ...self::NO_AI, 'uses' => 2, 'input_tokens' => 300, 'output_tokens' => 30, 'restaurants' => 1,
                'cost' => 0.0001, 'credits' => 4, 'translation_uses' => 2, 'translation_cost' => 0.0001,
            ])
            ->assertJsonPath('data.writing_help_month', [
                'uses' => 5, 'input_tokens' => 1000, 'output_tokens' => 120, 'restaurants' => 2, 'cost' => 0.00035,
                'credits' => 13, 'translation_uses' => 3, 'translation_cost' => 0.00015, 'description_uses' => 2, 'description_cost' => 0.0002,
            ]);
    }

    public function test_the_income_is_what_the_paying_restaurants_pay_a_month_per_currency(): void
    {
        $this->freezeTime();
        Package::findBySlug('premium')->update(['currency' => 'EUR']);
        // Pro's $7.99 through Whish, a $3.00 deal on Free, and Premium at its own 15.00 euros.
        $this->ownerOn('pro', ['pays_with_whish' => true]);
        $this->ownerOn('free', ['price_cents' => 300]);
        $this->ownerOn('premium', ['price_cents' => 1500]);
        // Not paying: switched off, ended, not started yet, on Free.
        $this->ownerOn('pro', ['is_active' => false, 'pays_with_whish' => true]);
        $this->ownerOn('premium', ['package_ends_at' => now()->subDay()]);
        $this->ownerOn('pro', ['package_started_at' => now()->addDay()]);
        $this->ownerOn('free');

        $this->withToken($this->admin()->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/summary')
            ->assertOk()
            ->assertJsonPath('data.income', [
                'paying' => 3,
                'paying_whish' => 1,
                // The most first.
                'totals' => [
                    ['currency' => 'EUR', 'cents' => 1500, 'whish_cents' => 0],
                    ['currency' => 'USD', 'cents' => 1099, 'whish_cents' => 799],
                ],
            ]);
    }

    public function test_a_given_package_price_counts_for_the_package_and_nothing_counts_for_free(): void
    {
        // Pro given for nothing, and Premium at its own price.
        $this->ownerOn('pro', ['price_cents' => 0]);
        $this->ownerOn('premium');

        $this->withToken($this->admin()->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/summary')
            ->assertJsonPath('data.income', [
                'paying' => 1,
                'paying_whish' => 0,
                'totals' => [['currency' => 'USD', 'cents' => 1000, 'whish_cents' => 0]],
            ]);
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
