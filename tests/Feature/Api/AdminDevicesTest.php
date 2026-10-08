<?php

namespace Tests\Feature\Api;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The admin app's phones (`PUT`/`DELETE /api/admin/devices`): the address
 * notifications go to.
 */
class AdminDevicesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function as(User $user): static
    {
        $this->app['auth']->forgetGuards();

        return $this->withToken($user->createToken('phone')->plainTextToken);
    }

    public function test_a_phone_is_kept_once_however_often_it_is_sent(): void
    {
        $admin = $this->admin();

        $this->as($admin)->putJson('/api/admin/devices', ['token' => 'fcm-token-1', 'platform' => 'android'])->assertNoContent();
        $this->as($admin)->putJson('/api/admin/devices', ['token' => 'fcm-token-1', 'platform' => 'android'])->assertNoContent();

        $phone = DeviceToken::sole();
        $this->assertSame($admin->id, $phone->user_id);
        $this->assertSame('android', $phone->platform);
        $this->assertNotNull($phone->last_seen_at);
    }

    public function test_a_phone_signed_in_by_another_admin_moves_to_them(): void
    {
        $first = $this->admin();
        $second = $this->admin();

        $this->as($first)->putJson('/api/admin/devices', ['token' => 'shared-phone', 'platform' => 'ios'])->assertNoContent();
        $this->as($second)->putJson('/api/admin/devices', ['token' => 'shared-phone', 'platform' => 'ios'])->assertNoContent();

        $this->assertSame($second->id, DeviceToken::sole()->user_id);
    }

    public function test_signing_out_removes_only_ones_own_phone(): void
    {
        $admin = $this->admin();
        $mine = DeviceToken::factory()->for($admin)->create();
        $theirs = DeviceToken::factory()->for($this->admin())->create();

        $this->as($admin)->deleteJson('/api/admin/devices', ['token' => $theirs->token])->assertNoContent();
        $this->assertModelExists($theirs);

        $this->as($admin)->deleteJson('/api/admin/devices', ['token' => $mine->token])->assertNoContent();
        $this->assertModelMissing($mine);
    }

    public function test_it_checks_the_token_and_the_platform(): void
    {
        $this->as($this->admin())->putJson('/api/admin/devices', ['token' => str_repeat('a', 513), 'platform' => 'windows'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['token', 'platform']);
    }

    public function test_only_admins_register_phones(): void
    {
        $this->putJson('/api/admin/devices', ['token' => 'x', 'platform' => 'android'])->assertUnauthorized();

        $this->as($this->owner()->user)->putJson('/api/admin/devices', ['token' => 'x', 'platform' => 'android'])->assertForbidden();
        $this->assertSame(0, DeviceToken::count());
    }
}
