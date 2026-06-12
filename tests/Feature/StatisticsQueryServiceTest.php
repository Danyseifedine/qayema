<?php

namespace Tests\Feature;

use App\Models\Restaurant;
use App\Models\RestaurantStatistic;
use App\Services\StatisticsQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StatisticsQueryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_aggregates_metrics_for_a_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create();

        RestaurantStatistic::factory()->create([
            'restaurant_id' => $restaurant->id, 'session_id' => 'a', 'viewed_at' => now(),
            'via_qr' => true, 'whatsapp_orders' => 2, 'device_type' => 'mobile', 'page_views' => 1,
        ]);
        RestaurantStatistic::factory()->create([
            'restaurant_id' => $restaurant->id, 'session_id' => 'a', 'viewed_at' => now(),
            'via_qr' => false, 'whatsapp_orders' => 0, 'device_type' => 'mobile', 'page_views' => 1,
        ]);
        RestaurantStatistic::factory()->create([
            'restaurant_id' => $restaurant->id, 'session_id' => 'b', 'viewed_at' => now(),
            'via_qr' => false, 'whatsapp_orders' => 1, 'device_type' => 'desktop', 'page_views' => 1,
        ]);

        $payload = app(StatisticsQueryService::class)->build($restaurant, '30');

        $this->assertSame('30', $payload['period']);
        $this->assertSame(3, $payload['totalViews']);
        $this->assertSame(2, $payload['uniqueVisitors']);
        $this->assertSame(3, $payload['periodViews']);
        $this->assertSame(1, $payload['qrVisits']);
        $this->assertSame(3, $payload['whatsappOrders']);
        $this->assertSame(2, $payload['deviceBreakdown']['mobile']);
        $this->assertSame(1, $payload['deviceBreakdown']['desktop']);
    }

    public function test_empty_payload_is_zeroed(): void
    {
        $payload = app(StatisticsQueryService::class)->emptyPayload();

        $this->assertNull($payload['restaurant']);
        $this->assertSame(0, $payload['totalViews']);
        $this->assertSame(0, $payload['uniqueVisitors']);
        $this->assertSame([], $payload['deviceBreakdown']);
    }
}
