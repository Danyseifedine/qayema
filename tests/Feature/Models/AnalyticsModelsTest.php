<?php

namespace Tests\Feature\Models;

use App\Enums\MenuEventType;
use App\Models\MenuEvent;
use App\Models\MenuSession;
use App\Models\Restaurant;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The two rows analytics is built from: a visit and a thing a guest did.
 */
class AnalyticsModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_menu_event_belongs_to_its_restaurant_and_casts_its_type(): void
    {
        $restaurant = Restaurant::factory()->create();

        $event = MenuEvent::factory()->for($restaurant)->create([
            'type' => 'search_miss',
            'value' => 'shawarma',
            'occurred_at' => '2026-09-01 12:30:00',
        ])->fresh();

        $this->assertTrue($event->restaurant->is($restaurant));
        $this->assertSame(MenuEventType::SearchMiss, $event->type);
        $this->assertInstanceOf(CarbonInterface::class, $event->occurred_at);
        $this->assertSame('2026-09-01 12:30:00', $event->occurred_at->toDateTimeString());
        $this->assertFalse($event->usesTimestamps());
    }

    public function test_a_menu_session_belongs_to_its_restaurant_and_casts_its_flags(): void
    {
        $restaurant = Restaurant::factory()->create();

        $session = MenuSession::factory()->for($restaurant)->create([
            'via_qr' => 1,
            'viewed_at' => '2026-09-02 20:00:00',
        ])->fresh();

        $this->assertTrue($session->restaurant->is($restaurant));
        $this->assertTrue($session->via_qr);
        $this->assertSame('2026-09-02 20:00:00', $session->viewed_at->toDateTimeString());
    }

    public function test_deleting_a_restaurant_takes_its_analytics_with_it(): void
    {
        $event = MenuEvent::factory()->create();
        $session = MenuSession::factory()->create(['restaurant_id' => $event->restaurant_id]);

        $event->restaurant->delete();

        $this->assertModelMissing($event);
        $this->assertModelMissing($session);
    }
}
