<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Admin\Pages\ManagePaddlePrices;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\RelationManagers\CoinTransactionsRelationManager;
use App\Models\CoinPack;
use App\Models\User;
use App\Services\Global\PaddlePrices;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class CoinAdminTest extends TestCase
{
    use RefreshDatabase;

    private const PRICE = 'pri_starter';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cashier.api_key' => 'test-key', 'cashier.sandbox' => true]);
        PaddlePrices::flush();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function pack(int $coins = 500): CoinPack
    {
        return CoinPack::factory()->withCoins($coins)->create([
            'slug' => 'starter',
            'paddle_price_id_sandbox' => self::PRICE,
        ]);
    }

    private function fakePrice(string $amount = '1000'): void
    {
        Http::fake([
            'https://*api.paddle.com/prices/*' => Http::response(['data' => []]),
            'https://*api.paddle.com/prices*' => Http::response([
                'data' => [[
                    'id' => self::PRICE,
                    'unit_price' => ['amount' => $amount, 'currency_code' => 'USD'],
                ]],
            ]),
        ]);
        PaddlePrices::flush();
    }

    public function test_an_admin_can_give_a_user_coins(): void
    {
        $owner = User::factory()->create(['role' => UserRole::MenuOwner]);

        $this->actingAs($this->admin());

        Livewire::test(CoinTransactionsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => EditUser::class,
        ])
            ->callAction(TestAction::make('grant')->table(), data: [
                'amount' => 750,
                'note' => 'Launch gift',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(750, (int) $owner->fresh()->coin_balance);
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $owner->id,
            'amount' => 750,
            'type' => 'admin_grant',
        ]);
    }

    public function test_an_admin_can_take_coins_back(): void
    {
        $owner = User::factory()->create(['role' => UserRole::MenuOwner]);
        $owner->wallet()->credit(500);

        $this->actingAs($this->admin());

        Livewire::test(CoinTransactionsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => EditUser::class,
        ])
            ->callAction(TestAction::make('deduct')->table(), data: ['amount' => 200]);

        $this->assertSame(300, (int) $owner->fresh()->coin_balance);
    }

    public function test_taking_back_more_than_the_balance_is_refused(): void
    {
        $owner = User::factory()->create(['role' => UserRole::MenuOwner]);
        $owner->wallet()->credit(100);

        $this->actingAs($this->admin());

        Livewire::test(CoinTransactionsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => EditUser::class,
        ])
            ->callAction(TestAction::make('deduct')->table(), data: ['amount' => 500]);

        $this->assertSame(100, (int) $owner->fresh()->coin_balance, 'The balance is left alone.');
        $this->assertDatabaseCount('coin_transactions', 1);
    }

    public function test_the_ledger_lists_the_users_rows(): void
    {
        $owner = User::factory()->create(['role' => UserRole::MenuOwner]);
        $row = $owner->wallet()->credit(500);

        $this->actingAs($this->admin());

        Livewire::test(CoinTransactionsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => EditUser::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$row]);
    }

    public function test_the_price_page_pushes_a_change_to_paddle(): void
    {
        $this->pack(500);
        $this->fakePrice('1000');

        $this->actingAs($this->admin());

        Livewire::test(ManagePaddlePrices::class)
            ->assertOk()
            // Mounts as dollars: 1000 cents.
            ->assertSchemaStateSet(['prices.starter' => '10.00'])
            ->fillForm(['prices.starter' => '12.50'])
            ->call('save');

        Http::assertSent(fn ($request): bool => $request->method() === 'PATCH'
            && str_contains($request->url(), 'prices/'.self::PRICE)
            && $request['unit_price']['amount'] === '1250');
    }

    public function test_the_price_page_skips_an_unchanged_price(): void
    {
        $this->pack(500);
        $this->fakePrice('1000');

        $this->actingAs($this->admin());

        Livewire::test(ManagePaddlePrices::class)
            ->fillForm(['prices.starter' => '10.00'])
            ->call('save');

        Http::assertNotSent(fn ($request): bool => $request->method() === 'PATCH');
    }

    public function test_an_owner_cannot_manage_coin_packs(): void
    {
        $owner = User::factory()->create(['role' => UserRole::MenuOwner]);

        $this->actingAs($owner)
            ->get(ManagePaddlePrices::getUrl())
            ->assertForbidden();
    }
}
