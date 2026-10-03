<?php

namespace Tests\Feature\Console;

use App\Models\Order;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A guest's phone number, address and location leave an order after 90
 * days; the order itself stays.
 */
class ForgetGuestDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_scheduled_nightly(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'orders:forget-guests'));

        $this->assertNotNull($event, 'orders:forget-guests is not on the schedule');
        $this->assertSame('20 3 * * *', $event->expression);
    }

    public function test_details_past_ninety_days_are_cleared_and_the_order_kept(): void
    {
        $old = Order::factory()->inMenu()->create(['placed_at' => now()->subDays(90)->subMinute(), 'latitude' => '33.9', 'longitude' => '35.5', 'total' => '12.00']);
        $edge = Order::factory()->inMenu()->create(['placed_at' => now()->subDays(90)->addMinute()]);

        $this->artisan('orders:forget-guests')->assertSuccessful()->expectsOutputToContain('Cleared guest details from 1 orders');

        $old->refresh();
        $this->assertNull($old->guest_name);
        $this->assertNull($old->guest_phone);
        $this->assertNull($old->address);
        $this->assertNull($old->latitude);
        $this->assertNull($old->longitude);
        $this->assertSame('12.00', (string) $old->total);
        $this->assertSame('+96170123456', $edge->fresh()->guest_phone);
    }

    public function test_a_custom_window_can_be_passed(): void
    {
        $order = Order::factory()->inMenu()->create(['placed_at' => now()->subDays(10)]);

        $this->artisan('orders:forget-guests', ['--days' => 7])->assertSuccessful();

        $this->assertNull($order->fresh()->guest_phone);
    }
}
