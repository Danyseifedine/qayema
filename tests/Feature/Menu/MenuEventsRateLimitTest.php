<?php

namespace Tests\Feature\Menu;

use App\Models\MenuEvent;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * `throttle:menu-events`: 300 a minute per IP, and never a ban, because a
 * whole dining room sends its guests' events from one address.
 */
class MenuEventsRateLimitTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const IP = '203.0.113.9';

    private const PER_MINUTE = 300;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.trusted_ips' => []]);
        $this->freezeTime();
    }

    private function send(Restaurant $restaurant, bool $json = true): TestResponse
    {
        $request = $this->withServerVariables(['REMOTE_ADDR' => self::IP]);
        $body = ['events' => [['type' => 'whatsapp']]];

        return $json
            ? $request->postJson(route('public.events', $restaurant->slug), $body)
            : $request->post(route('public.events', $restaurant->slug), $body);
    }

    /** Every one of the minute's 300 batches, as real requests. */
    private function useUpTheMinute(Restaurant $restaurant): void
    {
        for ($i = 0; $i < self::PER_MINUTE; $i++) {
            $this->send($restaurant)->assertNoContent();
        }
    }

    /**
     * The same, faster: 299 hits straight on the limiter under the key the
     * `throttle` middleware uses, then the last one as a real request.
     */
    private function nearlyUseUpTheMinute(Restaurant $restaurant): void
    {
        $key = md5('menu-events'.self::IP);

        for ($i = 1; $i < self::PER_MINUTE; $i++) {
            RateLimiter::hit($key, 60);
        }

        $this->send($restaurant)->assertNoContent();
    }

    public function test_the_three_hundred_and_first_batch_in_a_minute_is_a_429(): void
    {
        $restaurant = $this->published();

        $this->useUpTheMinute($restaurant);

        $this->send($restaurant)
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertExactJson([
                'message' => 'Too many requests. Please slow down.',
                'code' => 'too_many_requests',
                'retry_after' => 60,
            ]);

        $this->assertSame(self::PER_MINUTE, MenuEvent::query()->count());
    }

    public function test_a_non_json_request_gets_a_plain_429(): void
    {
        $restaurant = $this->published();

        $this->nearlyUseUpTheMinute($restaurant);

        $response = $this->send($restaurant, json: false)->assertStatus(429);

        $this->assertSame('Too many requests.', $response->getContent());
    }

    /** Far past AbuseGuard's strike threshold, and still no ban. */
    public function test_hammering_the_limit_never_bans_the_ip(): void
    {
        $restaurant = $this->published();

        $this->nearlyUseUpTheMinute($restaurant);

        for ($i = 0; $i < 30; $i++) {
            $this->send($restaurant)->assertStatus(429);
        }

        $this->assertDatabaseCount('blocked_ips', 0);
        $this->withServerVariables(['REMOTE_ADDR' => self::IP])
            ->get(route('public.menu', $restaurant->slug))
            ->assertOk();
    }

    public function test_the_limit_resets_after_a_minute(): void
    {
        $restaurant = $this->published();

        $this->nearlyUseUpTheMinute($restaurant);
        $this->send($restaurant)->assertStatus(429);

        $this->travel(61)->seconds();

        $this->send($restaurant)->assertNoContent();
    }

    /** The limit is per IP: a second dining room is not held up by the first. */
    public function test_another_ip_is_not_affected(): void
    {
        $restaurant = $this->published();

        $this->nearlyUseUpTheMinute($restaurant);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.7'])
            ->postJson(route('public.events', $restaurant->slug), ['events' => [['type' => 'map']]])
            ->assertNoContent();
    }
}
