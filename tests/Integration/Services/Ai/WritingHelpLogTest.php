<?php

namespace Tests\Integration\Services\Ai;

use App\Models\WritingHelpDay;
use App\Services\Ai\WritingHelpLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Services\Ai\WritingHelpLog: what the writing help cost, a row per
 * restaurant per day of its own.
 */
class WritingHelpLogTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function log(): WritingHelpLog
    {
        return app(WritingHelpLog::class);
    }

    public function test_requests_on_one_day_add_up_on_one_row(): void
    {
        $owner = $this->owner();

        $this->log()->record($owner, true, 300, 20);
        $this->log()->record($owner, true, 310, 25);

        $day = WritingHelpDay::query()->sole();
        $this->assertSame($owner->id, $day->restaurant_id);
        $this->assertSame(2, $day->uses);
        $this->assertSame(610, $day->input_tokens);
        $this->assertSame(45, $day->output_tokens);
    }

    public function test_each_request_is_priced_when_made_and_a_later_model_never_reprices_it(): void
    {
        $owner = $this->owner();
        config(['services.writing_help.provider' => 'anthropic', 'services.writing_help.model' => 'claude-haiku-5-5']);

        // 1,000,000 in and 200,000 out on Haiku 5.5: $0.10 + $0.10.
        $this->log()->record($owner, true, 1_000_000, 200_000);
        $this->assertSame('0.200000', WritingHelpDay::query()->sole()->cost);

        // Sonnet 5.5 from now on: the new request at its price, the first kept.
        config(['services.writing_help.model' => 'claude-sonnet-5-5']);
        $this->log()->record($owner, true, 1000, 100);
        $this->assertSame('0.203000', WritingHelpDay::query()->sole()->cost);
    }

    public function test_a_failure_spends_tokens_but_no_use(): void
    {
        $owner = $this->owner();

        $this->log()->record($owner, false, 600, 10);

        $this->assertSame(0, WritingHelpDay::query()->sole()->uses);
        $this->assertSame(600, WritingHelpDay::query()->sole()->input_tokens);
    }

    public function test_a_request_that_spent_nothing_writes_nothing(): void
    {
        $this->log()->record($this->owner(), false, 0, 0);

        $this->assertSame(0, WritingHelpDay::query()->count());
    }

    public function test_the_day_is_the_restaurants_own(): void
    {
        // 23:30 in Beirut is already the 12th in Tokyo.
        $this->travelTo(Carbon::parse('2026-10-11 23:30', 'Asia/Beirut'));
        $beirut = $this->owner(['timezone' => 'Asia/Beirut']);
        $tokyo = $this->owner(['timezone' => 'Asia/Tokyo']);

        $this->log()->record($beirut, true, 1, 1);
        $this->log()->record($tokyo, true, 1, 1);

        $this->assertSame('2026-10-11', WritingHelpDay::query()->where('restaurant_id', $beirut->id)->sole()->day->toDateString());
        $this->assertSame('2026-10-12', WritingHelpDay::query()->where('restaurant_id', $tokyo->id)->sole()->day->toDateString());
    }

    public function test_a_new_day_is_a_new_row(): void
    {
        $owner = $this->owner(['timezone' => 'UTC']);
        $this->travelTo(Carbon::parse('2026-10-11 23:00', 'UTC'));
        $this->log()->record($owner, true, 1, 1);

        $this->travel(2)->hours();
        $this->log()->record($owner, true, 1, 1);

        $this->assertSame(2, WritingHelpDay::query()->count());
    }

    public function test_a_restaurant_reads_today_and_its_last_thirty_days(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 12:00', 'UTC'));
        $owner = $this->owner(['timezone' => 'UTC']);
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-11', 'uses' => 3, 'input_tokens' => 900, 'output_tokens' => 100, 'cost' => '0.000140']);
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-09-20', 'uses' => 10, 'input_tokens' => 3000, 'output_tokens' => 400, 'cost' => '0.000500']);
        // Thirty days ago is out of the window; another restaurant never counts.
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-09-11', 'uses' => 50]);
        WritingHelpDay::factory()->create(['day' => '2026-10-11', 'uses' => 7]);

        $this->assertSame(['today' => 3, 'uses' => 13, 'tokens' => 4400, 'cost' => 0.00064], $this->log()->of($owner));
    }

    public function test_a_restaurant_that_never_used_it_reads_nothing(): void
    {
        $this->assertSame(['today' => 0, 'uses' => 0, 'tokens' => 0, 'cost' => 0.0], $this->log()->of($this->owner()));
    }

    public function test_everyones_last_thirty_days_for_the_admin(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 12:00', 'UTC'));
        $first = $this->owner();
        $second = $this->owner();
        WritingHelpDay::factory()->for($first)->create(['day' => '2026-10-11', 'uses' => 3, 'input_tokens' => 900, 'output_tokens' => 100, 'cost' => '0.000140', 'credits' => 5, 'translation_uses' => 2, 'translation_cost' => '0.000100', 'description_uses' => 1, 'description_cost' => '0.000040']);
        WritingHelpDay::factory()->for($first)->create(['day' => '2026-10-01', 'uses' => 2, 'input_tokens' => 500, 'output_tokens' => 50, 'cost' => '0.000075', 'credits' => 2, 'translation_uses' => 2, 'translation_cost' => '0.000075']);
        WritingHelpDay::factory()->for($second)->create(['day' => '2026-10-05', 'uses' => 1, 'input_tokens' => 300, 'output_tokens' => 30]);
        WritingHelpDay::factory()->create(['day' => '2026-08-01', 'uses' => 99, 'credits' => 99]);

        $this->assertSame([
            'uses' => 6, 'tokens' => 1880, 'restaurants' => 2, 'cost' => 0.000215, 'cost_today' => 0.00014,
            // What the credits' prices are checked against.
            'credits' => 7, 'translation_uses' => 4, 'translation_cost' => 0.000175, 'description_uses' => 1, 'description_cost' => 0.00004,
        ], $this->log()->overall());
    }

    public function test_no_use_at_all_reads_zero_everywhere(): void
    {
        $this->assertSame([
            'uses' => 0, 'tokens' => 0, 'restaurants' => 0, 'cost' => 0.0, 'cost_today' => 0.0,
            'credits' => 0, 'translation_uses' => 0, 'translation_cost' => 0.0, 'description_uses' => 0, 'description_cost' => 0.0,
        ], $this->log()->overall());
    }

    public function test_a_restaurant_deleted_takes_its_days_with_it(): void
    {
        $owner = $this->owner();
        $this->log()->record($owner, true, 1, 1);

        $owner->delete();

        $this->assertSame(0, WritingHelpDay::query()->count());
    }
}
