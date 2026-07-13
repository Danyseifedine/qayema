<?php

namespace Tests\Feature;

use App\Models\Feature;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Transaction;
use Tests\TestCase;

class PurchasesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A completed one-time transaction plus the grant it delivered.
     *
     * @return array{0: User, 1: Transaction}
     */
    private function ownerWithPurchase(): array
    {
        $dishLimit = Feature::factory()->limit()->create([
            'slug' => 'dish_limit',
            'name' => ['en' => 'Dish limit', 'ar' => 'حد الأطباق'],
        ]);
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $user = $restaurant->user;

        $transaction = $user->transactions()->create([
            'paddle_id' => 'txn_1',
            'paddle_subscription_id' => null,
            'invoice_number' => 'INV-001',
            'status' => 'completed',
            'total' => '500',
            'tax' => '0',
            'currency' => 'USD',
            'billed_at' => now(),
        ]);

        $restaurant->featureGrants()->create([
            'feature_id' => $dishLimit->id,
            'value' => '100',
            'source' => 'purchase',
            'reference' => 'txn_1',
            'starts_at' => now(),
        ]);

        return [$user, $transaction];
    }

    public function test_purchases_require_authentication(): void
    {
        $this->getJson(route('api.purchases.index'))->assertUnauthorized();
    }

    public function test_purchases_list_transactions_with_the_granted_items(): void
    {
        [$user] = $this->ownerWithPurchase();

        $this->actingAs($user)
            ->getJson(route('api.purchases.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.invoice_number', 'INV-001')
            ->assertJsonPath('data.0.status', 'completed')
            ->assertJsonPath('data.0.total', 500)
            ->assertJsonPath('data.0.currency', 'USD')
            ->assertJsonPath('data.0.items.0.slug', 'dish_limit')
            ->assertJsonPath('data.0.items.0.value', 100)
            ->assertJsonPath('data.0.items.0.name.en', 'Dish limit');
    }

    public function test_purchases_never_include_another_users_transactions(): void
    {
        $this->ownerWithPurchase();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->getJson(route('api.purchases.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_invoice_returns_a_paddle_url_for_the_owners_transaction(): void
    {
        config(['cashier.api_key' => 'test-key']);
        Http::fake([
            'https://*api.paddle.com/transactions/txn_1/invoice' => Http::response([
                'data' => ['url' => 'https://paddle.example/invoice.pdf'],
            ]),
        ]);

        [$user, $transaction] = $this->ownerWithPurchase();

        $this->actingAs($user)
            ->getJson(route('api.purchases.invoice', $transaction))
            ->assertOk()
            ->assertJsonPath('url', 'https://paddle.example/invoice.pdf');
    }

    public function test_invoice_is_not_found_for_a_foreign_transaction(): void
    {
        [, $transaction] = $this->ownerWithPurchase();
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->getJson(route('api.purchases.invoice', $transaction))
            ->assertNotFound();
    }

    public function test_invoice_is_not_found_when_the_transaction_has_no_invoice(): void
    {
        [$user] = $this->ownerWithPurchase();

        $uninvoiced = $user->transactions()->create([
            'paddle_id' => 'txn_2',
            'paddle_subscription_id' => null,
            'invoice_number' => null,
            'status' => 'completed',
            'total' => '250',
            'tax' => '0',
            'currency' => 'USD',
            'billed_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson(route('api.purchases.invoice', $uninvoiced))
            ->assertNotFound();
    }
}
