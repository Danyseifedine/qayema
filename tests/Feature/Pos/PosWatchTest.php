<?php

namespace Tests\Feature\Pos;

use App\Enums\Feature;
use App\Jobs\SyncPosCatalog;
use App\Mail\PosUnreachable;
use App\Models\PosConnection;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A POS that stops answering its menu: after an hour the owner is emailed
 * and the card says since when, once per outage; a pull that works clears it.
 */
class PosWatchTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->defaultPackageIncludes(Feature::PosConnection);
        $this->shop = $this->owner(['currency' => 'USD']);
    }

    private function failing(int $minutesSinceLastSync): PosConnection
    {
        return PosConnection::factory()->for($this->shop)->create(['address' => 'https://pos.test'])->forceFill([
            'last_synced_at' => now()->subMinutes($minutesSinceLastSync),
            'last_attempt_at' => now(),
            'last_error' => 'Your POS did not answer. Check its address and that it is online.',
        ]);
    }

    public function test_an_hour_without_an_answer_is_told_to_the_owner_once(): void
    {
        $connection = tap($this->failing(70))->save();

        $this->artisan('pos:watch')->assertSuccessful();
        $this->artisan('pos:watch')->assertSuccessful();

        Mail::assertQueued(PosUnreachable::class, 1);
        Mail::assertQueued(PosUnreachable::class, fn (PosUnreachable $mail): bool => $mail->hasTo($this->shop->user->email));
        $this->assertNotNull($connection->fresh()->alerted_at);
        $this->actingAs($this->shop->user)->getJson(route('api.pos-connection.show'))
            ->assertJsonPath('data.unreachable_since', $connection->fresh()->last_synced_at->toIso8601String());
    }

    public function test_a_short_hiccup_or_a_link_that_answers_is_not_told(): void
    {
        tap($this->failing(20))->save();

        $this->artisan('pos:watch')->assertSuccessful();

        Mail::assertNothingQueued();
        $this->actingAs($this->shop->user)->getJson(route('api.pos-connection.show'))->assertJsonPath('data.unreachable_since', null);
    }

    public function test_a_pull_that_works_clears_it_so_the_next_outage_is_told_again(): void
    {
        $connection = tap($this->failing(70))->save();
        $this->artisan('pos:watch');
        $catalog = json_decode((string) file_get_contents(base_path('tests/Fixtures/pos-contract/catalog.json')), true);
        $catalog['data']['products'][0]['image'] = null;
        Http::fake(['pos.test/*' => Http::response($catalog)]);

        app()->call([new SyncPosCatalog($this->shop->id), 'handle']);

        $this->assertNull($connection->fresh()->alerted_at);
        $this->assertNull($connection->fresh()->last_error);
    }

    public function test_the_mail_says_what_went_wrong_and_where_to_look(): void
    {
        $connection = tap($this->failing(70))->save();

        $html = (new PosUnreachable($this->shop, $connection))->render();

        $this->assertStringContainsString('Your POS did not answer.', $html);
        $this->assertStringContainsString('/features', $html);
        $this->assertStringNotContainsString($connection->key, $html);
    }
}
