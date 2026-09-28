<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\BlockedIps\BlockedIpResource;
use App\Filament\Admin\Resources\BlockedIps\Pages\CreateBlockedIp;
use App\Filament\Admin\Resources\BlockedIps\Pages\EditBlockedIp;
use App\Filament\Admin\Resources\BlockedIps\Pages\ListBlockedIps;
use App\Models\BlockedIp;
use App\Services\Security\AbuseGuard;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * The blocked IPs resource: the list with real rows, its status filter,
 * unblocking one or many, and editing a block so it takes effect at once.
 */
class BlockedIpAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_list_shows_permanent_and_timed_blocks_newest_first(): void
    {
        $older = BlockedIp::factory()->create(['ip' => '203.0.113.1', 'reason' => 'spam', 'created_at' => now()->subDay()]);
        $newer = BlockedIp::factory()->create(['ip' => '203.0.113.2', 'expires_at' => now()->addHour()]);

        Livewire::test(ListBlockedIps::class)
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true)
            ->assertSee('203.0.113.1')
            ->assertSee('spam')
            ->assertSee('Permanent');
    }

    public function test_the_status_filter_splits_active_and_expired_blocks(): void
    {
        $permanent = BlockedIp::factory()->create();
        $timed = BlockedIp::factory()->create(['expires_at' => now()->addHour()]);
        $expired = BlockedIp::factory()->expired()->create();

        Livewire::test(ListBlockedIps::class)
            ->filterTable('active', true)
            ->assertCanSeeTableRecords([$permanent, $timed])
            ->assertCanNotSeeTableRecords([$expired]);

        Livewire::test(ListBlockedIps::class)
            ->filterTable('active', false)
            ->assertCanSeeTableRecords([$expired])
            ->assertCanNotSeeTableRecords([$permanent, $timed]);

        Livewire::test(ListBlockedIps::class)
            ->filterTable('active', null)
            ->assertCanSeeTableRecords([$permanent, $timed, $expired]);
    }

    public function test_the_list_searches_by_ip_and_reason(): void
    {
        $spam = BlockedIp::factory()->create(['ip' => '198.51.100.7', 'reason' => 'comment spam']);
        $scraper = BlockedIp::factory()->create(['ip' => '192.0.2.44', 'reason' => 'scraper']);

        Livewire::test(ListBlockedIps::class)
            ->searchTable('198.51')
            ->assertCanSeeTableRecords([$spam])
            ->assertCanNotSeeTableRecords([$scraper]);

        Livewire::test(ListBlockedIps::class)
            ->searchTable('scraper')
            ->assertCanSeeTableRecords([$scraper])
            ->assertCanNotSeeTableRecords([$spam]);
    }

    public function test_unblock_from_the_row_lifts_the_block_at_once(): void
    {
        $guard = app(AbuseGuard::class);
        $guard->block('203.0.113.20');
        $this->assertTrue($guard->isBlocked('203.0.113.20'));
        $block = BlockedIp::query()->where('ip', '203.0.113.20')->sole();

        Livewire::test(ListBlockedIps::class)
            ->callAction(TestAction::make('delete')->table($block));

        $this->assertDatabaseMissing('blocked_ips', ['id' => $block->id]);
        $this->assertFalse($guard->isBlocked('203.0.113.20'));
    }

    public function test_unblock_selected_removes_only_the_selected_blocks(): void
    {
        [$first, $second, $kept] = BlockedIp::factory()->count(3)->create()->all();

        Livewire::test(ListBlockedIps::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertDatabaseMissing('blocked_ips', ['id' => $first->id]);
        $this->assertDatabaseMissing('blocked_ips', ['id' => $second->id]);
        $this->assertDatabaseHas('blocked_ips', ['id' => $kept->id]);
    }

    public function test_setting_an_expiry_in_the_past_lifts_a_cached_block(): void
    {
        $guard = app(AbuseGuard::class);
        $guard->block('203.0.113.30', 'spam');
        $this->assertTrue($guard->isBlocked('203.0.113.30'), 'The answer is now cached.');
        $block = BlockedIp::query()->where('ip', '203.0.113.30')->sole();

        $this->get(BlockedIpResource::getUrl('edit', ['record' => $block]))->assertOk()->assertSee('203.0.113.30');

        Livewire::test(EditBlockedIp::class, ['record' => $block->id])
            ->assertSchemaStateSet(['ip' => '203.0.113.30', 'reason' => 'spam', 'expires_at' => null])
            ->fillForm(['expires_at' => now()->subMinute(), 'reason' => 'forgiven'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('forgiven', $block->fresh()->reason);
        $this->assertTrue($block->fresh()->expires_at->isPast());
        $this->assertFalse($guard->isBlocked('203.0.113.30'));
    }

    public function test_the_edit_form_requires_an_ip_of_at_most_45_characters(): void
    {
        $block = BlockedIp::factory()->create(['ip' => '203.0.113.40']);

        Livewire::test(EditBlockedIp::class, ['record' => $block->id])
            ->fillForm(['ip' => ''])
            ->call('save')
            ->assertHasFormErrors(['ip' => 'required']);

        Livewire::test(EditBlockedIp::class, ['record' => $block->id])
            ->fillForm(['ip' => str_repeat('1', 46)])
            ->call('save')
            ->assertHasFormErrors(['ip' => 'max']);

        $this->assertSame('203.0.113.40', $block->fresh()->ip);
    }

    public function test_create_refuses_a_missing_ip_and_an_overlong_reason(): void
    {
        Livewire::test(CreateBlockedIp::class)
            ->fillForm(['ip' => '', 'reason' => str_repeat('x', 256)])
            ->call('create')
            ->assertHasFormErrors(['ip' => 'required', 'reason' => 'max']);

        $this->assertDatabaseCount('blocked_ips', 0);
    }

    public function test_create_stores_a_timed_block(): void
    {
        $until = now()->addDay()->startOfMinute();

        Livewire::test(CreateBlockedIp::class)
            ->fillForm(['ip' => '2001:db8::1', 'expires_at' => $until, 'reason' => 'probe'])
            ->call('create')
            ->assertHasNoFormErrors();

        $block = BlockedIp::query()->where('ip', '2001:db8::1')->sole();
        $this->assertTrue($block->expires_at->equalTo($until));
        $this->assertTrue(app(AbuseGuard::class)->isBlocked('2001:db8::1'));
    }

    public function test_the_create_page_renders_over_http(): void
    {
        $this->get(BlockedIpResource::getUrl('create'))->assertOk();
    }
}
