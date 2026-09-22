<?php

namespace Tests\Feature\Templates;

use App\Enums\CoinTransactionType;
use App\Exceptions\InsufficientCoins;
use App\Models\Restaurant;
use App\Models\Template;
use App\Services\Global\TemplateStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateStoreTest extends TestCase
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

    public function test_unlocking_spends_the_coins_and_records_the_purchase(): void
    {
        $restaurant = $this->ownerWithCoins(1000);
        $template = Template::factory()->paid(400)->create();

        $purchase = app(TemplateStore::class)->unlock($restaurant, $template);

        $this->assertSame(400, $purchase->price_paid);
        $this->assertSame(600, (int) $restaurant->user->fresh()->coin_balance);
        $this->assertTrue($restaurant->fresh()->owns($template));

        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $restaurant->user_id,
            'amount' => -400,
            'type' => 'spend',
            'reference' => 'template:'.$purchase->id,
        ]);

        // The purchase points back at the exact ledger row that paid for it.
        $this->assertNotNull($purchase->fresh()->coin_transaction_id);
    }

    public function test_unlocking_without_enough_coins_charges_nothing(): void
    {
        $restaurant = $this->ownerWithCoins(100);
        $template = Template::factory()->paid(400)->create();

        try {
            app(TemplateStore::class)->unlock($restaurant, $template);
            $this->fail('Expected InsufficientCoins.');
        } catch (InsufficientCoins $exception) {
            $this->assertSame(300, $exception->shortfall());
        }

        $this->assertSame(100, (int) $restaurant->user->fresh()->coin_balance);
        $this->assertDatabaseCount('template_purchases', 0);
        $this->assertFalse($restaurant->fresh()->owns($template));
    }

    public function test_unlocking_the_same_template_twice_only_charges_once(): void
    {
        $restaurant = $this->ownerWithCoins(1000);
        $template = Template::factory()->paid(400)->create();

        $first = app(TemplateStore::class)->unlock($restaurant, $template);
        $second = app(TemplateStore::class)->unlock($restaurant, $template);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(600, (int) $restaurant->user->fresh()->coin_balance);
        $this->assertDatabaseCount('template_purchases', 1);
    }

    public function test_ownership_survives_switching_away_and_back(): void
    {
        $restaurant = $this->ownerWithCoins(1000);
        $free = Template::factory()->create();
        $paid = Template::factory()->paid(400)->create();

        $store = app(TemplateStore::class);
        $store->unlock($restaurant, $paid);
        $store->select($restaurant, $paid);
        $store->select($restaurant, $free);
        $store->select($restaurant, $paid);

        $this->assertSame(600, (int) $restaurant->user->fresh()->coin_balance, 'Switching back is free.');
        $this->assertSame($paid->id, $restaurant->fresh()->template_id);
    }

    public function test_a_later_price_change_does_not_alter_what_was_paid(): void
    {
        $restaurant = $this->ownerWithCoins(1000);
        $template = Template::factory()->paid(400)->create();

        $purchase = app(TemplateStore::class)->unlock($restaurant, $template);

        $template->update(['price' => 900]);

        $this->assertSame(400, $purchase->fresh()->price_paid);
        $this->assertTrue($restaurant->fresh()->owns($template), 'Still owned at the old price.');
    }

    public function test_selecting_seeds_the_templates_default_settings(): void
    {
        $restaurant = $this->ownerWithCoins(0);
        $template = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#C8A85A'],
        ])->create();

        app(TemplateStore::class)->select($restaurant, $template);

        $this->assertSame(['primary_color' => '#C8A85A'], $restaurant->fresh()->template_settings);
    }

    public function test_one_restaurants_purchase_does_not_unlock_it_for_another(): void
    {
        $buyer = $this->ownerWithCoins(1000);
        $bystander = $this->ownerWithCoins(1000);
        $template = Template::factory()->paid(400)->create();

        app(TemplateStore::class)->unlock($buyer, $template);

        $this->assertTrue($buyer->fresh()->owns($template));
        $this->assertFalse($bystander->fresh()->owns($template));
    }
}
