<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Global\FeatureCatalog;
use App\Services\Global\FeatureFulfillment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Paddle\Events\TransactionCompleted;
use Laravel\Paddle\Transaction;
use Tests\TestCase;

class FeaturePurchaseTest extends TestCase
{
    use RefreshDatabase;

    private const DISH_PRICE = 'pri_dish_test';

    /**
     * Configure a sellable dish slot and return a fresh owner + restaurant whose
     * dish_limit sits at the seeded default floor.
     *
     * @return array{0: User, 1: Restaurant}
     */
    private function ownerWithSellableDishSlot(): array
    {
        config(['paddle.catalog.dish.price_id' => self::DISH_PRICE]);

        Feature::factory()->limit()->create(['slug' => 'dish_limit']);

        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        return [$restaurant->user, $restaurant];
    }

    /**
     * The Paddle line quantity is in dishes (the checkout multiplies packs by the
     * step), so fulfillment grants it directly.
     *
     * @param  array<int, array<string, mixed>>  $items
     */
    private function fulfill(User $user, string $transactionId, array $items): void
    {
        app(FeatureFulfillment::class)->fulfill($user, $transactionId, $items);
    }

    public function test_purchasing_dishes_stacks_additively_on_the_base_limit(): void
    {
        [$user, $restaurant] = $this->ownerWithSellableDishSlot();
        $base = $restaurant->dish_limit;

        // Two packs = 100 dishes on the Paddle line.
        $this->fulfill($user, 'txn_1', [
            ['price' => ['id' => self::DISH_PRICE], 'quantity' => 100],
        ]);

        $this->assertSame($base + 100, $restaurant->fresh()->dish_limit);
        $this->assertDatabaseHas('restaurant_features', [
            'restaurant_id' => $restaurant->id,
            'source' => 'purchase',
            'reference' => 'txn_1',
            'value' => '100',
        ]);
    }

    public function test_separate_purchases_accumulate(): void
    {
        [$user, $restaurant] = $this->ownerWithSellableDishSlot();
        $base = $restaurant->dish_limit;

        $this->fulfill($user, 'txn_1', [['price' => ['id' => self::DISH_PRICE], 'quantity' => 50]]);
        $this->fulfill($user, 'txn_2', [['price' => ['id' => self::DISH_PRICE], 'quantity' => 50]]);

        $this->assertSame($base + 100, $restaurant->fresh()->dish_limit);
        $this->assertSame(2, $restaurant->featureGrants()->where('source', 'purchase')->count());
    }

    public function test_a_purchase_is_idempotent_per_transaction(): void
    {
        [$user, $restaurant] = $this->ownerWithSellableDishSlot();
        $base = $restaurant->dish_limit;
        $items = [['price' => ['id' => self::DISH_PRICE], 'quantity' => 50]];

        // A re-delivered webhook must not grant the same transaction twice.
        $this->fulfill($user, 'txn_1', $items);
        $this->fulfill($user, 'txn_1', $items);

        $this->assertSame($base + 50, $restaurant->fresh()->dish_limit);
        $this->assertSame(1, $restaurant->featureGrants()->where('reference', 'txn_1')->count());
    }

    public function test_transaction_completed_event_grants_the_purchased_dishes(): void
    {
        [$user, $restaurant] = $this->ownerWithSellableDishSlot();
        $base = $restaurant->dish_limit;

        $transaction = new Transaction;
        $transaction->paddle_id = 'txn_evt';

        $payload = ['data' => ['items' => [
            ['price' => ['id' => self::DISH_PRICE], 'quantity' => 150],
        ]]];

        event(new TransactionCompleted($user, $transaction, $payload));

        $this->assertSame($base + 150, $restaurant->fresh()->dish_limit);
    }

    public function test_a_price_not_in_the_catalog_is_ignored(): void
    {
        [$user, $restaurant] = $this->ownerWithSellableDishSlot();
        $base = $restaurant->dish_limit;

        $this->fulfill($user, 'txn_1', [
            ['price' => ['id' => 'pri_unknown'], 'quantity' => 250],
        ]);

        $this->assertSame($base, $restaurant->fresh()->dish_limit);
        $this->assertSame(0, $restaurant->featureGrants()->where('source', 'purchase')->count());
    }

    public function test_checkout_requires_authentication(): void
    {
        config(['paddle.catalog.dish.price_id' => self::DISH_PRICE]);

        $this->postJson(route('api.checkout'), ['items' => [['id' => 'dish', 'quantity' => 1]]])
            ->assertUnauthorized();
    }

    public function test_checkout_rejects_an_unknown_addon(): void
    {
        [$user] = $this->ownerWithSellableDishSlot();

        $this->actingAs($user)
            ->postJson(route('api.checkout'), ['items' => [['id' => 'unicorn', 'quantity' => 1]]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.id');
    }

    public function test_checkout_rejects_a_quantity_over_the_maximum(): void
    {
        [$user] = $this->ownerWithSellableDishSlot();

        // 21 packs × 50 = 1050 dishes, over the Paddle price maximum of 1000.
        $this->actingAs($user)
            ->postJson(route('api.checkout'), ['items' => [['id' => 'dish', 'quantity' => 21]]])
            ->assertStatus(422)
            ->assertJsonValidationErrors('items.0.quantity');
    }

    public function test_catalog_resolves_the_price_id_for_the_active_paddle_environment(): void
    {
        config([
            'cashier.sandbox' => true,
            'paddle.catalog.dish.price_id' => ['sandbox' => 'pri_sb', 'production' => 'pri_live'],
        ]);
        $this->assertSame('pri_sb', app(FeatureCatalog::class)->find('dish')['price_id']);

        config(['cashier.sandbox' => false]);
        $this->assertSame('pri_live', app(FeatureCatalog::class)->find('dish')['price_id']);

        config(['paddle.catalog.dish.price_id' => ['sandbox' => 'pri_sb', 'production' => null]]);
        $this->assertNull(app(FeatureCatalog::class)->find('dish'), 'An entry without a price for the active environment is not sellable.');
    }

    public function test_checkout_requires_a_restaurant(): void
    {
        config(['paddle.catalog.dish.price_id' => self::DISH_PRICE]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.checkout'), ['items' => [['id' => 'dish', 'quantity' => 1]]])
            ->assertForbidden();
    }
}
