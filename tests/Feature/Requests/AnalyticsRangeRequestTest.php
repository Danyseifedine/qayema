<?php

namespace Tests\Feature\Requests;

use App\Enums\Feature;
use App\Models\MenuSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * App\Http\Requests\AnalyticsRangeRequest, through GET /api/analytics and
 * GET /api/analytics/advanced.
 */
class AnalyticsRangeRequestTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Analytics, Feature::AdvancedAnalytics);
    }

    /** @return array<string, array{0: string}> */
    public static function ranges(): array
    {
        return ['7d' => ['7d'], '30d' => ['30d'], '90d' => ['90d'], 'all' => ['all']];
    }

    /** @return array<string, array{0: mixed}> */
    public static function badRanges(): array
    {
        return [
            'unknown' => ['1y'],
            'upper case' => ['7D'],
            'a number' => [7],
            'an array' => [['7d']],
        ];
    }

    #[DataProvider('ranges')]
    public function test_every_known_range_is_accepted(string $range): void
    {
        $restaurant = $this->owner();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics', ['range' => $range]))
            ->assertOk()
            ->assertJsonPath('data.range', $range);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.advanced', ['range' => $range]))
            ->assertOk()
            ->assertJsonPath('data.range', $range);
    }

    #[DataProvider('badRanges')]
    public function test_anything_else_is_refused(mixed $range): void
    {
        $restaurant = $this->owner();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics', ['range' => $range]))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['range' => 'The selected range is invalid.']);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.advanced', ['range' => $range]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('range');
    }

    public function test_no_range_means_thirty_days(): void
    {
        $restaurant = $this->owner();
        MenuSession::factory()->for($restaurant)->create(['viewed_at' => now()->subDays(40)]);
        MenuSession::factory()->for($restaurant)->create(['viewed_at' => now()->subDays(20)]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics'))
            ->assertOk()
            ->assertJsonPath('data.range', '30d')
            ->assertJsonPath('data.totals.views', 1);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.advanced'))
            ->assertOk()
            ->assertJsonPath('data.range', '30d');
    }

    /** A blank `?range=` is "no range", not a range called "". */
    public function test_a_blank_range_also_means_thirty_days(): void
    {
        $restaurant = $this->owner();
        MenuSession::factory()->for($restaurant)->create(['viewed_at' => now()->subDays(40)]);
        MenuSession::factory()->for($restaurant)->create(['viewed_at' => now()->subDays(20)]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics').'?range=')
            ->assertOk()
            ->assertJsonPath('data.range', '30d')
            ->assertJsonPath('data.totals.views', 1);
    }

    /** Without advanced analytics a blank range must not read as a longer one. */
    public function test_a_blank_range_is_open_to_basic_analytics(): void
    {
        $restaurant = $this->ownerOn('pro');

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics').'?range=')
            ->assertOk()
            ->assertJsonPath('data.range', '30d');
    }

    public function test_a_user_without_a_restaurant_is_refused_before_validation(): void
    {
        $user = $this->userWithoutRestaurant();

        $this->actingAs($user)
            ->getJson(route('api.analytics', ['range' => 'nonsense']))
            ->assertForbidden()
            ->assertExactJson(['message' => 'This action is unauthorized.', 'code' => 'forbidden']);

        $this->actingAs($user)
            ->getJson(route('api.analytics.advanced'))
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }
}
