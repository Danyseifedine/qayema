<?php

namespace Tests\Feature\Templates;

use App\Enums\CoinTransactionType;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateUnlockApiTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithCoins(int $coins): Restaurant
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        if ($coins > 0) {
            $restaurant->user->wallet()->credit($coins, CoinTransactionType::AdminGrant);
        }

        return $restaurant;
    }

    public function test_unlock_requires_authentication(): void
    {
        $this->postJson(route('api.templates.unlock'), ['template_id' => 1])->assertUnauthorized();
    }

    public function test_unlocking_a_paid_template_spends_coins(): void
    {
        $restaurant = $this->ownerWithCoins(1000);
        $template = Template::factory()->paid(400)->create(['slug' => 'premium']);

        $response = $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.unlock'), ['template_id' => $template->id])
            ->assertOk();

        // The listing comes back with the template now owned and the new balance.
        $owned = collect($response->json('data'))->firstWhere('slug', 'premium');
        $this->assertTrue($owned['owned']);
        $response->assertJsonPath('meta.balance', 600);
    }

    public function test_unlocking_without_enough_coins_returns_the_shortfall(): void
    {
        $restaurant = $this->ownerWithCoins(150);
        $template = Template::factory()->paid(400)->create();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.unlock'), ['template_id' => $template->id])
            ->assertStatus(402)
            ->assertJsonPath('balance', 150)
            ->assertJsonPath('needed', 400)
            ->assertJsonPath('shortfall', 250);

        $this->assertSame(150, (int) $restaurant->user->fresh()->coin_balance);
    }

    public function test_unlocking_a_free_template_is_rejected(): void
    {
        $restaurant = $this->ownerWithCoins(1000);
        $template = Template::factory()->create();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.unlock'), ['template_id' => $template->id])
            ->assertStatus(422);

        $this->assertSame(1000, (int) $restaurant->user->fresh()->coin_balance);
    }

    public function test_unlocking_something_already_owned_is_rejected(): void
    {
        $restaurant = $this->ownerWithCoins(1000);
        $template = Template::factory()->paid(400)->create();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.unlock'), ['template_id' => $template->id])
            ->assertOk();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.unlock'), ['template_id' => $template->id])
            ->assertStatus(422);

        $this->assertSame(600, (int) $restaurant->user->fresh()->coin_balance, 'Charged once.');
    }

    public function test_unlocking_an_inactive_template_is_rejected(): void
    {
        $restaurant = $this->ownerWithCoins(1000);
        $template = Template::factory()->paid(400)->inactive()->create();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.unlock'), ['template_id' => $template->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_id');
    }

    public function test_the_full_loop_unlock_then_select(): void
    {
        $restaurant = $this->ownerWithCoins(500);
        $template = Template::factory()->paid(500)->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#C8A85A'],
        ])->create();

        // Selecting before unlocking is refused.
        $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.select'), ['template_id' => $template->id])
            ->assertForbidden();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.unlock'), ['template_id' => $template->id])
            ->assertOk();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.select'), ['template_id' => $template->id])
            ->assertOk()
            ->assertJsonPath('meta.current', $template->id)
            ->assertJsonPath('meta.settings.primary_color', '#C8A85A');

        $this->assertSame(0, (int) $restaurant->user->fresh()->coin_balance);
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $template = Template::factory()->paid(400)->create();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.templates.unlock'), ['template_id' => $template->id])
            ->assertForbidden();
    }
}
