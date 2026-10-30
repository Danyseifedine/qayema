<?php

namespace Tests\Feature\Pos;

use App\Enums\Feature;
use App\Jobs\SyncPosCatalog;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\PosConnection;
use App\Models\Restaurant;
use App\Services\Pos\PosSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Connect to POS from the owner's side: linking with the POS's address and
 * key, the card's view of the link, Sync now, the POS's signed webhooks,
 * the hourly pull, ending the link, and what a connected menu locks.
 */
class PosConnectionTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const KEY = 'menu_abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG';

    private const UUID = '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d';

    private Restaurant $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages, Feature::PosConnection, Feature::Offers, Feature::BuildYourOwn);
        $this->shop = $this->owner(['second_locale' => 'ar', 'currency' => 'USD']);
    }

    private function fakePos(array $connection = []): void
    {
        $info = json_decode((string) file_get_contents(base_path('tests/Fixtures/pos-contract/connection.json')), true);
        $info['data'] = [...$info['data'], ...$connection];

        Http::fake([
            'pos.test/api/v1/menu/connection' => fn ($request) => $request->method() === 'GET'
                ? Http::response($info)
                : Http::response(['success' => true, 'message' => null, 'data' => $info['data']]),
            'pos.test/*' => Http::response(['success' => false, 'message' => 'Not found.', 'errors' => [], 'code' => 'not_found', 'data' => null], 404),
        ]);
    }

    private function linked(): PosConnection
    {
        return PosConnection::factory()->for($this->shop)->create(['pos_uuid' => self::UUID, 'key' => self::KEY, 'address' => 'https://pos.test']);
    }

    public function test_an_owner_links_with_the_address_and_key_and_the_catalog_is_pulled(): void
    {
        Bus::fake();
        $this->fakePos();

        $this->actingAs($this->shop->user)->postJson(route('api.pos-connection.store'), ['address' => 'pos.test/', 'key' => self::KEY])
            ->assertCreated()
            ->assertJsonPath('data.branch', 'Hamra')
            ->assertJsonPath('data.address', 'https://pos.test')
            ->assertJsonMissingPath('data.key')
            ->assertJsonMissingPath('data.signing_secret');

        $connection = PosConnection::query()->sole();
        $this->assertSame(self::KEY, $connection->key);
        $this->assertSame(self::UUID, $connection->pos_uuid);
        $this->assertNotSame(self::KEY, $connection->getRawOriginal('key'));
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && $request['webhook_url'] === route('api.pos.webhook')
            && $request['signing_secret'] === $connection->signing_secret
            && $request->hasHeader('Authorization', 'Bearer '.self::KEY));
        Bus::assertDispatched(SyncPosCatalog::class, fn (SyncPosCatalog $job) => $job->restaurantId === $this->shop->id);
    }

    public function test_linking_is_refused_with_a_reason_the_owner_can_act_on(): void
    {
        $this->fakePos(['currency' => 'LBP']);
        $this->actingAs($this->shop->user)->postJson(route('api.pos-connection.store'), ['address' => 'https://pos.test', 'key' => self::KEY])
            ->assertJsonValidationErrorFor('key');

        Http::fake(['pos.test/*' => Http::response(['success' => false, 'message' => 'Invalid', 'errors' => [], 'code' => 'invalid_key', 'data' => null], 401)]);
        $this->actingAs($this->shop->user)->postJson(route('api.pos-connection.store'), ['address' => 'https://pos.test', 'key' => self::KEY])
            ->assertJsonValidationErrorFor('key');

        $this->actingAs($this->shop->user)->postJson(route('api.pos-connection.store'), ['address' => 'ftp://pos.test', 'key' => self::KEY])
            ->assertJsonValidationErrorFor('address');

        $this->assertSame(0, PosConnection::query()->count());
    }

    public function test_a_branch_already_linked_to_another_menu_is_refused(): void
    {
        $this->fakePos();
        PosConnection::factory()->create(['pos_uuid' => self::UUID]);

        $this->actingAs($this->shop->user)->postJson(route('api.pos-connection.store'), ['address' => 'https://pos.test', 'key' => self::KEY])
            ->assertJsonValidationErrorFor('key');
    }

    public function test_only_where_an_admin_switched_connect_to_pos_on(): void
    {
        $this->defaultPackageSets(Feature::PosConnection, 0);
        $other = $this->owner();

        $this->actingAs($other->user)->getJson(route('api.pos-connection.show'))->assertForbidden();
        $this->actingAs($other->user)->postJson(route('api.pos-connection.store'), ['address' => 'https://pos.test', 'key' => self::KEY])->assertForbidden();
    }

    public function test_the_card_shows_the_link_and_a_second_link_is_refused(): void
    {
        $this->linked()->forceFill(['problems' => [['type' => 'photo', 'message' => 'The photo of "Hummus" could not be fetched.']]])->save();

        $this->actingAs($this->shop->user)->getJson(route('api.pos-connection.show'))
            ->assertOk()
            ->assertJsonPath('data.problems.0.type', 'photo')
            ->assertJsonMissingPath('data.key');

        $this->actingAs($this->shop->user)->postJson(route('api.pos-connection.store'), ['address' => 'https://pos.test', 'key' => self::KEY])->assertConflict();
        $this->assertTrue($this->shop->user->fresh()->restaurant->isConnectedToPos());
    }

    public function test_sync_now_queues_a_pull(): void
    {
        Bus::fake();
        $this->linked();

        $this->actingAs($this->shop->user)->postJson(route('api.pos-connection.sync'))->assertAccepted();

        Bus::assertDispatched(SyncPosCatalog::class);
    }

    public function test_disconnecting_tells_the_pos_and_ends_the_link_even_when_it_does_not_answer(): void
    {
        Http::fake(['pos.test/*' => Http::response('', 500)]);
        $this->linked();

        $this->actingAs($this->shop->user)->deleteJson(route('api.pos-connection.destroy'))->assertNoContent();

        $this->assertSame(0, PosConnection::query()->count());
        Http::assertSent(fn ($request) => $request->method() === 'DELETE');
    }

    public function test_the_job_applies_the_catalog_and_keeps_what_happened(): void
    {
        $connection = $this->linked();
        $catalog = json_decode((string) file_get_contents(base_path('tests/Fixtures/pos-contract/catalog.json')), true);
        $catalog['data']['products'][0]['image'] = null;
        $answer = Http::response($catalog);
        Http::fake(function () use (&$answer) {
            return $answer;
        });

        app()->call([new SyncPosCatalog($this->shop->id), 'handle']);

        $this->assertNotNull($connection->fresh()->last_synced_at);
        $this->assertNull($connection->fresh()->last_error);
        $this->assertSame('301', Dish::query()->sole()->pos_id);

        $answer = Http::response(['success' => false, 'message' => 'x', 'errors' => [], 'code' => 'invalid_key', 'data' => null], 401);

        app()->call([new SyncPosCatalog($this->shop->id), 'handle']);

        $this->assertStringContainsString('refused the key', (string) $connection->fresh()->last_error);
    }

    public function test_the_hourly_command_queues_a_pull_for_each_link(): void
    {
        Bus::fake();
        $this->linked();
        PosConnection::factory()->create();

        $this->artisan('pos:sync')->assertSuccessful();

        Bus::assertDispatchedTimes(SyncPosCatalog::class, 2);
    }

    private function webhook(PosConnection $connection, array $payload, ?int $timestamp = null, ?string $secret = null): \Illuminate\Testing\TestResponse
    {
        $body = (string) json_encode($payload);
        $timestamp ??= time();

        return $this->call('POST', route('api.pos.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_POS_CONNECTION' => $connection->pos_uuid,
            'HTTP_X_POS_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_POS_SIGNATURE' => PosSignature::sign($secret ?? $connection->signing_secret, $timestamp, $body),
        ], $body);
    }

    public function test_a_signed_catalog_ping_queues_a_pull_once(): void
    {
        Bus::fake();
        $connection = $this->linked();
        $payload = ['event' => 'catalog.changed', 'at' => now()->toIso8601String()];
        $timestamp = time();

        $this->webhook($connection, $payload, $timestamp)->assertAccepted();
        $this->webhook($connection, $payload, $timestamp)->assertOk();

        Bus::assertDispatchedTimes(SyncPosCatalog::class, 1);
    }

    public function test_an_unsigned_wrongly_signed_or_stale_ping_is_refused(): void
    {
        Bus::fake();
        $connection = $this->linked();
        $payload = ['event' => 'catalog.changed'];

        $this->webhook($connection, $payload, secret: 'not-the-secret')->assertUnauthorized();
        $this->webhook($connection, $payload, timestamp: time() - 600)->assertUnauthorized();
        $this->postJson(route('api.pos.webhook'), $payload)->assertUnauthorized();

        Bus::assertNotDispatched(SyncPosCatalog::class);
    }

    public function test_a_key_revoked_in_the_pos_ends_the_link(): void
    {
        Http::fake();
        $connection = $this->linked();

        $this->webhook($connection, ['event' => 'connection.revoked'])->assertAccepted();

        $this->assertSame(0, PosConnection::query()->count());
        Http::assertNothingSent();
    }

    public function test_while_connected_the_pos_owns_the_categories_and_dishes(): void
    {
        $this->linked();
        $category = Category::factory()->for($this->shop)->create(['name' => ['en' => 'Mezze'], 'pos_id' => '12']);
        $dish = Dish::factory()->for($this->shop)->create(['category_id' => $category->id, 'name' => ['en' => 'Hummus'], 'pos_id' => '301', 'price' => '5.50']);
        $user = $this->shop->user;

        $this->actingAs($user)->postJson(route('api.categories.store'), ['name' => ['en' => 'New']])->assertJsonValidationErrorFor('pos');
        $this->actingAs($user)->postJson(route('api.dishes.store'), ['name' => ['en' => 'New'], 'price' => 1, 'category_id' => $category->id])->assertJsonValidationErrorFor('pos');
        $this->actingAs($user)->deleteJson(route('api.categories.destroy', $category))->assertJsonValidationErrorFor('pos');
        $this->actingAs($user)->deleteJson(route('api.dishes.destroy', $dish))->assertJsonValidationErrorFor('pos');
        $this->actingAs($user)->patchJson(route('api.dishes.availability', $dish), ['is_available' => false])->assertJsonValidationErrorFor('pos');

        $this->actingAs($user)->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Changed'], 'price' => 9, 'icon' => 'chef-hat'])
            ->assertOk()
            ->assertJsonPath('data.from_pos', true);
        $this->actingAs($user)->patchJson(route('api.categories.update', $category), ['name' => ['en' => 'Changed'], 'icon' => 'salad'])
            ->assertOk()
            ->assertJsonPath('data.from_pos', true);

        $dish->refresh();
        $this->assertSame(['Hummus', '5.50', 'chef-hat'], [$dish->getTranslation('name', 'en'), (string) $dish->price, $dish->icon]);
        $this->assertSame(['Mezze', 'salad'], [$category->fresh()->getTranslation('name', 'en'), $category->fresh()->icon]);
        $this->actingAs($user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.pos_connected', true);
    }

    public function test_while_connected_offers_and_build_your_own_are_off_and_come_back_on_disconnect(): void
    {
        $this->linked();
        $offer = Offer::factory()->for($this->shop)->create();
        $restaurant = $this->shop->fresh();

        $this->assertFalse($restaurant->showsOffers());
        $this->assertFalse($restaurant->showsBuilds());
        $this->actingAs($this->shop->user)->getJson(route('api.offers.index'))->assertOk()->assertJsonCount(1, 'data');
        $this->actingAs($this->shop->user)->postJson(route('api.offers.reorder'), ['ids' => [$offer->id]])->assertJsonValidationErrorFor('pos');

        $restaurant->posConnection->delete();
        $restaurant = $this->shop->fresh();

        $this->assertTrue($restaurant->showsOffers());
        $this->assertTrue($restaurant->showsBuilds());
    }

    public function test_the_guest_menu_shows_only_what_the_pos_sent_while_connected(): void
    {
        $this->shop = $this->published(['currency' => 'USD']);
        $posCategory = Category::factory()->for($this->shop)->create(['name' => ['en' => 'From the POS'], 'pos_id' => '12']);
        $gone = Category::factory()->for($this->shop)->create(['name' => ['en' => 'Gone from the POS'], 'pos_id' => '13', 'pos_removed_at' => now()]);
        $own = Category::factory()->for($this->shop)->create(['name' => ['en' => 'Made by hand']]);
        Dish::factory()->for($this->shop)->create(['category_id' => $posCategory->id, 'name' => ['en' => 'POS hummus'], 'pos_id' => '301']);
        Dish::factory()->for($this->shop)->create(['category_id' => $gone->id, 'name' => ['en' => 'Hidden falafel'], 'pos_id' => '302']);
        Dish::factory()->for($this->shop)->create(['category_id' => $own->id, 'name' => ['en' => 'Hand made tabbouleh']]);
        $this->linked();

        $this->get($this->shop->fresh()->publicUrl())
            ->assertOk()
            ->assertSee('POS hummus')
            ->assertDontSee('Hidden falafel')
            ->assertDontSee('Hand made tabbouleh')
            ->assertDontSee('Made by hand');

        PosConnection::query()->delete();

        $this->get($this->shop->fresh()->publicUrl())->assertSee('Hand made tabbouleh');
    }
}
