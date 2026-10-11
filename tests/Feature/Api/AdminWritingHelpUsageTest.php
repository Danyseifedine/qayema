<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Admin\WritingHelpUsageController;
use App\Models\WritingHelpDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The admin app's AI page (`GET /api/admin/writing-help`): what the writing
 * help spent today or over the last 30 days, in all and per restaurant.
 */
class AdminWritingHelpUsageTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-12 10:00', 'Asia/Beirut'));
        $this->withToken($this->admin()->createToken('phone')->plainTextToken);
    }

    public function test_today_lists_each_restaurant_the_most_tokens_first(): void
    {
        $small = $this->owner(['name' => ['en' => 'Beit Rami']]);
        $big = $this->owner(['name' => ['en' => 'Moudi Grill']]);
        WritingHelpDay::factory()->for($small)->create(['day' => '2026-10-12', 'uses' => 5, 'input_tokens' => 400, 'output_tokens' => 50]);
        WritingHelpDay::factory()->for($big)->create(['day' => '2026-10-12', 'uses' => 2, 'input_tokens' => 3000, 'output_tokens' => 200]);
        WritingHelpDay::factory()->for($small)->create(['day' => '2026-10-02', 'uses' => 30, 'input_tokens' => 90000, 'output_tokens' => 900]);

        $this->getJson('/api/admin/writing-help')
            ->assertOk()
            ->assertJsonPath('data.range', 'today')
            ->assertJsonPath('data.since', '2026-10-12')
            ->assertJsonPath('data.uses', 7)
            ->assertJsonPath('data.input_tokens', 3400)
            ->assertJsonPath('data.output_tokens', 250)
            ->assertJsonPath('data.restaurants', 2)
            ->assertJsonPath('data.by_restaurant.0.restaurant.name', 'Moudi Grill')
            ->assertJsonPath('data.by_restaurant.0.uses', 2)
            ->assertJsonPath('data.by_restaurant.0.input_tokens', 3000)
            ->assertJsonPath('data.by_restaurant.1.restaurant.id', $small->id)
            ->assertJsonPath('data.by_restaurant.1.input_tokens', 400)
            ->assertJsonCount(2, 'data.by_restaurant');
    }

    public function test_thirty_days_adds_up_each_restaurants_days(): void
    {
        $restaurant = $this->owner();
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-10-12', 'uses' => 1, 'input_tokens' => 100, 'output_tokens' => 10]);
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-09-13', 'uses' => 4, 'input_tokens' => 400, 'output_tokens' => 40]);
        // A day before the window.
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-09-12', 'uses' => 50, 'input_tokens' => 9000, 'output_tokens' => 900]);

        $this->getJson('/api/admin/writing-help?range=30d')
            ->assertOk()
            ->assertJsonPath('data.since', '2026-09-13')
            ->assertJsonPath('data.uses', 5)
            ->assertJsonPath('data.input_tokens', 500)
            ->assertJsonPath('data.by_restaurant.0.uses', 5)
            ->assertJsonPath('data.by_restaurant.0.output_tokens', 50);
    }

    public function test_a_quiet_day_is_all_zeros_and_an_empty_list(): void
    {
        $this->getJson('/api/admin/writing-help')
            ->assertExactJson(['data' => [
                'range' => 'today', 'since' => '2026-10-12',
                'uses' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'restaurants' => 0,
                'by_restaurant' => [],
            ]]);
    }

    public function test_the_list_stops_at_its_length(): void
    {
        WritingHelpDay::factory()->count(WritingHelpUsageController::LISTED + 2)->create(['day' => '2026-10-12']);

        $this->getJson('/api/admin/writing-help')
            ->assertJsonPath('data.restaurants', WritingHelpUsageController::LISTED + 2)
            ->assertJsonCount(WritingHelpUsageController::LISTED, 'data.by_restaurant');
    }

    public function test_a_range_it_does_not_know_is_refused(): void
    {
        $this->getJson('/api/admin/writing-help?range=year')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['range']);
    }

    public function test_only_an_admin_sees_it(): void
    {
        $this->app['auth']->forgetGuards();
        $owner = $this->owner()->user;

        $this->withToken($owner->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/writing-help')
            ->assertForbidden();
    }
}
