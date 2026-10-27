<?php

namespace Tests\Feature\Api;

use App\Http\Controllers\Api\Admin\WritingHelpUsageController;
use App\Models\Package;
use App\Models\WritingHelpDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The admin app's AI page (`GET /api/admin/writing-help`): what the writing
 * help spent today, over the last 30 days or this month, in all and per
 * restaurant next to what it pays, and the model with its price.
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
        WritingHelpDay::factory()->for($small)->create(['day' => '2026-10-12', 'uses' => 5, 'input_tokens' => 400, 'output_tokens' => 50, 'cost' => '0.000065']);
        WritingHelpDay::factory()->for($big)->create(['day' => '2026-10-12', 'uses' => 2, 'input_tokens' => 3000, 'output_tokens' => 200, 'cost' => '0.000400']);
        WritingHelpDay::factory()->for($small)->create(['day' => '2026-10-02', 'uses' => 30, 'input_tokens' => 90000, 'output_tokens' => 900]);

        $this->getJson('/api/admin/writing-help')
            ->assertOk()
            ->assertJsonPath('data.range', 'today')
            ->assertJsonPath('data.since', '2026-10-12')
            ->assertJsonPath('data.uses', 7)
            ->assertJsonPath('data.input_tokens', 3400)
            ->assertJsonPath('data.output_tokens', 250)
            ->assertJsonPath('data.restaurants', 2)
            // What it cost, as each request was priced when made.
            ->assertJsonPath('data.cost', 0.000465)
            ->assertJsonPath('data.by_restaurant.0.cost', 0.0004)
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
        config(['services.writing_help.provider' => null, 'services.writing_help.price_input' => null, 'services.writing_help.price_output' => null]);

        $this->getJson('/api/admin/writing-help')
            ->assertExactJson(['data' => [
                'range' => 'today', 'since' => '2026-10-12',
                'uses' => 0, 'input_tokens' => 0, 'output_tokens' => 0, 'restaurants' => 0, 'cost' => 0,
                'credits' => 0, 'translation_uses' => 0, 'translation_cost' => 0, 'description_uses' => 0, 'description_cost' => 0,
                // Off: no model and no price.
                'model' => ['name' => null, 'input_per_million' => null, 'output_per_million' => null],
                'by_restaurant' => [],
            ]]);
    }

    public function test_this_month_starts_on_the_first_and_counts_credits_and_each_kind(): void
    {
        $restaurant = $this->owner();
        WritingHelpDay::factory()->for($restaurant)->create([
            'day' => '2026-10-12', 'uses' => 3, 'input_tokens' => 300, 'output_tokens' => 30, 'cost' => '0.000300', 'credits' => 6,
            'translation_uses' => 1, 'translation_cost' => '0.000100', 'description_uses' => 2, 'description_cost' => '0.000200',
        ]);
        WritingHelpDay::factory()->for($restaurant)->create([
            'day' => '2026-10-01', 'uses' => 1, 'input_tokens' => 100, 'output_tokens' => 10, 'cost' => '0.000050', 'credits' => 2,
            'translation_uses' => 1, 'translation_cost' => '0.000050', 'description_uses' => 0, 'description_cost' => '0',
        ]);
        // Last month, though inside the last 30 days.
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-09-30', 'uses' => 20, 'input_tokens' => 2000, 'output_tokens' => 200, 'credits' => 40]);

        $this->getJson('/api/admin/writing-help?range=month')
            ->assertOk()
            ->assertJsonPath('data.range', 'month')
            ->assertJsonPath('data.since', '2026-10-01')
            ->assertJsonPath('data.uses', 4)
            ->assertJsonPath('data.input_tokens', 400)
            ->assertJsonPath('data.cost', 0.00035)
            ->assertJsonPath('data.credits', 8)
            ->assertJsonPath('data.translation_uses', 2)
            ->assertJsonPath('data.translation_cost', 0.00015)
            ->assertJsonPath('data.description_uses', 2)
            ->assertJsonPath('data.description_cost', 0.0002)
            ->assertJsonPath('data.by_restaurant.0.uses', 4);

        $this->getJson('/api/admin/writing-help?range=30d')
            ->assertJsonPath('data.uses', 24)
            ->assertJsonPath('data.credits', 48);
    }

    public function test_it_names_the_model_and_its_price_per_million_tokens(): void
    {
        config([
            'services.writing_help.provider' => 'anthropic',
            'services.writing_help.model' => 'claude-haiku-5-5',
            'services.writing_help.price_input' => null,
            'services.writing_help.price_output' => null,
        ]);

        $this->getJson('/api/admin/writing-help')
            ->assertJsonPath('data.model', ['name' => 'claude-haiku-5-5', 'input_per_million' => 0.1, 'output_per_million' => 0.5]);

        // A model the list does not know: the .env's price, else none.
        config(['services.writing_help.model' => 'gemini-9-flash']);
        $this->getJson('/api/admin/writing-help')
            ->assertJsonPath('data.model', ['name' => 'gemini-9-flash', 'input_per_million' => null, 'output_per_million' => null]);

        config(['services.writing_help.price_input' => '0.3', 'services.writing_help.price_output' => '2.5']);
        $this->getJson('/api/admin/writing-help')
            ->assertJsonPath('data.model', ['name' => 'gemini-9-flash', 'input_per_million' => 0.3, 'output_per_million' => 2.5]);
    }

    public function test_each_restaurant_shows_what_it_pays_a_month_while_it_pays(): void
    {
        Package::findBySlug('premium')->update(['currency' => 'EUR']);
        $pro = $this->ownerOn('pro');
        $deal = $this->ownerOn('premium', ['price_cents' => 1500]);
        $free = $this->ownerOn('free');
        $off = $this->ownerOn('pro', ['is_active' => false]);
        foreach ([$pro, $deal, $free, $off] as $i => $restaurant) {
            WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-10-12', 'input_tokens' => 4000 - $i * 1000]);
        }

        $this->getJson('/api/admin/writing-help')
            ->assertJsonPath('data.by_restaurant.0.restaurant.id', $pro->id)
            ->assertJsonPath('data.by_restaurant.0.pays_cents', 799)
            ->assertJsonPath('data.by_restaurant.0.currency', 'USD')
            ->assertJsonPath('data.by_restaurant.1.pays_cents', 1500)
            ->assertJsonPath('data.by_restaurant.1.currency', 'EUR')
            // On Free, or switched off: paying nothing now.
            ->assertJsonPath('data.by_restaurant.2.pays_cents', null)
            ->assertJsonPath('data.by_restaurant.3.restaurant.id', $off->id)
            ->assertJsonPath('data.by_restaurant.3.pays_cents', null)
            ->assertJsonPath('data.by_restaurant.3.currency', 'USD');
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
