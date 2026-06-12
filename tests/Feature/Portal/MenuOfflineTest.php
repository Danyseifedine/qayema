<?php

namespace Tests\Feature\Portal;

use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuOfflineTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_template_without_subscription_serves_unavailable_page_with_200(): void
    {
        $template = Template::factory()->paid()->create();
        $restaurant = Restaurant::factory()->create(['template_id' => $template->id, 'is_active' => true]);

        $response = $this->get('/'.$restaurant->slug);

        $response->assertOk();
        $response->assertViewIs('portal.menu.unavailable');
        $this->assertDatabaseCount('menu_sessions', 0);
    }

    public function test_paid_template_with_active_subscription_serves_the_menu(): void
    {
        $template = Template::factory()->paid()->create();
        $restaurant = Restaurant::factory()->create(['template_id' => $template->id, 'is_active' => true]);

        Subscription::factory()->create([
            'restaurant_id' => $restaurant->id,
            'subscribable_type' => Template::class,
            'subscribable_id' => $template->id,
        ]);

        $response = $this->get('/'.$restaurant->slug);

        $response->assertOk();
        $response->assertViewIs('portal.menu.designs.default');
    }

    public function test_free_template_always_serves_the_menu(): void
    {
        $template = Template::factory()->create(['tier' => 'free']);
        $restaurant = Restaurant::factory()->create(['template_id' => $template->id, 'is_active' => true]);

        $this->get('/'.$restaurant->slug)->assertOk()->assertViewIs('portal.menu.designs.default');
    }
}
