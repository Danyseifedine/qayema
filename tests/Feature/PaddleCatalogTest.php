<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\ManagePaddlePrices;
use App\Models\Feature;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Global\PaddlePrices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class PaddleCatalogTest extends TestCase
{
    use RefreshDatabase;

    private const DISH_PRICE = 'pri_dish_test';

    protected function setUp(): void
    {
        parent::setUp();

        // Pin the whole catalog so the suite is independent of config entries.
        config([
            'cashier.api_key' => 'test-key',
            'paddle.catalog' => [
                'dish' => [
                    'price_id' => self::DISH_PRICE,
                    'slug' => 'dish_limit',
                    'kind' => 'limit',
                    'step' => 50,
                    'max' => 1000,
                ],
            ],
        ]);

        PaddlePrices::flush();
    }

    /**
     * Fake the Paddle prices list endpoint with a $0.10/dish price.
     */
    private function fakePaddlePrices(): void
    {
        Http::fake([
            'https://*api.paddle.com/prices*' => Http::response([
                'data' => [
                    [
                        'id' => self::DISH_PRICE,
                        'unit_price' => ['amount' => '10', 'currency_code' => 'USD'],
                    ],
                ],
            ]),
        ]);
    }

    public function test_catalog_requires_authentication(): void
    {
        $this->getJson(route('api.catalog'))->assertUnauthorized();
    }

    public function test_catalog_requires_a_restaurant(): void
    {
        $this->fakePaddlePrices();

        $this->actingAs(User::factory()->create())
            ->getJson(route('api.catalog'))
            ->assertForbidden();
    }

    public function test_catalog_returns_entries_with_live_paddle_amounts(): void
    {
        $this->fakePaddlePrices();
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.catalog'))
            ->assertOk()
            ->assertJsonFragment([
                'id' => 'dish',
                'slug' => 'dish_limit',
                'kind' => 'limit',
                'step' => 50,
                'max' => 1000,
                'remaining' => 1000,
                'current' => 40,
                'unit_amount' => 10,
                'currency' => 'USD',
            ]);
    }

    public function test_catalog_reports_the_remaining_allowance_after_purchases(): void
    {
        $this->fakePaddlePrices();
        $dishLimit = Feature::factory()->limit()->create(['slug' => 'dish_limit']);
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $restaurant->featureGrants()->create([
            'feature_id' => $dishLimit->id,
            'value' => '900',
            'source' => 'purchase',
            'reference' => 'txn_prev',
            'starts_at' => now(),
        ]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.catalog'))
            ->assertOk()
            ->assertJsonPath('data.0.remaining', 100);
    }

    public function test_catalog_degrades_to_null_amounts_when_paddle_is_unreachable(): void
    {
        Http::fake([
            'https://*api.paddle.com/prices*' => Http::response([
                'error' => ['detail' => 'Service unavailable'],
            ], 503),
        ]);

        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.catalog'))
            ->assertOk()
            ->assertJsonPath('data.0.id', 'dish')
            ->assertJsonPath('data.0.unit_amount', null);
    }

    public function test_admin_page_pushes_a_changed_price_to_paddle(): void
    {
        $this->fakePaddlePrices();

        // The admin edits the PACK price: 10¢/dish × 50 = $5.00 per pack.
        // Saving $12.50/pack converts back to 25¢ per dish for Paddle.
        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ManagePaddlePrices::class)
            ->assertSet('data.prices.dish', '5.00')
            ->fillForm(['prices.dish' => '12.50'])
            ->call('save')
            ->assertNotified();

        Http::assertSent(function ($request): bool {
            return $request->method() === 'PATCH'
                && str_contains($request->url(), 'prices/'.self::DISH_PRICE)
                && $request['unit_price']['amount'] === '25'
                && $request['unit_price']['currency_code'] === 'USD';
        });
    }

    public function test_admin_page_skips_unchanged_prices(): void
    {
        $this->fakePaddlePrices();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test(ManagePaddlePrices::class)
            ->call('save')
            ->assertNotified();

        Http::assertNotSent(fn ($request): bool => $request->method() === 'PATCH');
    }

    public function test_updating_a_price_flushes_the_cached_amounts(): void
    {
        // In order: the priming GET, the PATCH, then the post-flush GET.
        Http::fake([
            'https://*api.paddle.com/prices*' => Http::sequence()
                ->push(['data' => [
                    ['id' => self::DISH_PRICE, 'unit_price' => ['amount' => '10', 'currency_code' => 'USD']],
                ]])
                ->push(['data' => ['id' => self::DISH_PRICE]])
                ->push(['data' => [
                    ['id' => self::DISH_PRICE, 'unit_price' => ['amount' => '25', 'currency_code' => 'USD']],
                ]]),
        ]);

        $this->assertSame(10, app(PaddlePrices::class)->amounts()['dish']['unit_amount']);

        app(PaddlePrices::class)->update('dish', 25);

        $this->assertSame(25, app(PaddlePrices::class)->amounts()['dish']['unit_amount'], 'The cache must be flushed after an update.');
    }
}
