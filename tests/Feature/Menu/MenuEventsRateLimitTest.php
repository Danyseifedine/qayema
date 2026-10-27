<?php

namespace Tests\Feature\Menu;

use App\Models\MenuEvent;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * `throttle:menu-events`: 120 a minute per IP and 20 a minute per guest on
 * that IP (the anonymous id the batch names), and never a ban, because a
 * whole dining room sends its guests' events from one address.
 */
class MenuEventsRateLimitTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    private const IP = '203.0.113.9';

    private const PER_MINUTE = 120;

    private const PER_GUEST = 20;

    protected function setUp(): void
    {
        parent::setUp();

        config(['security.trusted_ips' => []]);
        $this->freezeTime();
    }

    private function send(Restaurant $restaurant, bool $json = true, ?string $visitor = null): TestResponse
    {
        $request = $this->withServerVariables(['REMOTE_ADDR' => self::IP]);
        $body = array_filter(['visitor' => $visitor, 'events' => [['type' => 'whatsapp']]]);

        return $json
            ? $request->postJson(route('public.events', $restaurant->slug), $body)
            : $request->post(route('public.events', $restaurant->slug), $body);
    }

    private static function guest(int $number): string
    {
        return md5('guest'.$number);
    }

    /** Every one of the minute's 120 batches, as real requests from a room of guests. */
    private function useUpTheMinute(Restaurant $restaurant): void
    {
        for ($i = 0; $i < self::PER_MINUTE; $i++) {
            $this->send($restaurant, visitor: self::guest(intdiv($i, self::PER_GUEST)))->assertNoContent();
        }
    }

    /**
     * The same, faster: 119 hits straight on the limiter under the key the
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

    public function test_the_hundred_and_twenty_first_batch_in_a_minute_from_one_ip_is_a_429(): void
    {
        $restaurant = $this->published();

        $this->useUpTheMinute($restaurant);

        $this->send($restaurant, visitor: self::guest(99))
            ->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertExactJson([
                'message' => 'Too many requests. Please slow down.',
                'code' => 'too_many_requests',
                'retry_after' => 60,
            ]);

        $this->assertSame(self::PER_MINUTE, MenuEvent::query()->count());
    }

    /** One guest (or a script naming one) cannot use up the room's minute. */
    public function test_the_twenty_first_batch_in_a_minute_from_one_guest_is_a_429(): void
    {
        $restaurant = $this->published();

        for ($i = 0; $i < self::PER_GUEST; $i++) {
            $this->send($restaurant, visitor: self::guest(1))->assertNoContent();
        }

        $this->send($restaurant, visitor: self::guest(1))->assertStatus(429);
        $this->send($restaurant, visitor: self::guest(2))->assertNoContent();

        $this->assertSame(self::PER_GUEST + 1, MenuEvent::query()->count());
    }

    /** Without an id, the guest is its address and browser, as MenuEventsRequest::visitor() has it. */
    public function test_a_batch_without_an_id_counts_as_its_browser(): void
    {
        $restaurant = $this->published();

        for ($i = 0; $i < self::PER_GUEST; $i++) {
            $this->send($restaurant)->assertNoContent();
        }

        $this->send($restaurant)->assertStatus(429);
        $this->send($restaurant, visitor: 'not-an-id')->assertStatus(429);

        $this->withServerVariables(['REMOTE_ADDR' => self::IP, 'HTTP_USER_AGENT' => 'Another phone'])
            ->postJson(route('public.events', $restaurant->slug), ['events' => [['type' => 'map']]])
            ->assertNoContent();
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
