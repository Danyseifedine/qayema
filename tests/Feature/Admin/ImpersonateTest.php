<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ImpersonateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_impersonate_menu_owner_and_lands_in_their_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $menuOwner = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('impersonate', $menuOwner->id));

        $response->assertRedirect(config('app.dashboard_url'));
        $this->assertAuthenticatedAs($menuOwner);
    }

    public function test_menu_owner_cannot_impersonate_another_user(): void
    {
        $menuOwner = User::factory()->create();
        $other = User::factory()->create();

        $response = $this->actingAs($menuOwner)->get(route('impersonate', $other->id));

        $response->assertStatus(403);
        $this->assertAuthenticatedAs($menuOwner);
    }

    public function test_admin_cannot_impersonate_self(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('impersonate', $admin->id));

        $response->assertStatus(403);
        $this->assertAuthenticatedAs($admin);
    }

    public function test_guest_cannot_impersonate(): void
    {
        $menuOwner = User::factory()->create();

        $response = $this->get(route('impersonate', $menuOwner->id));

        $response->assertRedirect(route('login'));
    }

    public function test_impersonating_user_can_leave_impersonation(): void
    {
        $admin = User::factory()->admin()->create();
        $menuOwner = User::factory()->create();

        $this->actingAs($admin)->get(route('impersonate', $menuOwner->id));
        $this->assertAuthenticatedAs($menuOwner);

        $response = $this->actingAs($menuOwner)->get(route('impersonate.leave'));

        // Straight back to the Users list, where it started.
        $response->assertRedirect(route('filament.admin.resources.users.index'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_the_session_follows_the_owner_so_authenticate_session_keeps_them_in(): void
    {
        $admin = User::factory()->admin()->create();
        // Factory users share one password hash; this owner's must differ.
        $menuOwner = User::factory()->create(['password' => 'another-password']);

        // The admin panel's AuthenticateSession left the admin's hash in the
        // session; left there, the dashboard's first request signs the owner out.
        $this->actingAs($admin)
            ->withSession(['password_hash_web' => Auth::guard('web')->hashPasswordForCookie($admin->getAuthPassword())])
            ->get(route('impersonate', $menuOwner->id))
            ->assertRedirect(config('app.dashboard_url'));

        $this->assertSame(
            Auth::guard('web')->hashPasswordForCookie($menuOwner->getAuthPassword()),
            session('password_hash_web'),
        );
    }

    public function test_leaving_hands_the_session_back_to_the_admin(): void
    {
        $admin = User::factory()->admin()->create();
        $menuOwner = User::factory()->create(['password' => 'another-password']);

        $this->actingAs($admin)->get(route('impersonate', $menuOwner->id));
        $this->get(route('impersonate.leave'))->assertRedirect(route('filament.admin.resources.users.index'));

        $this->assertSame(
            Auth::guard('web')->hashPasswordForCookie($admin->getAuthPassword()),
            session('password_hash_web'),
        );
    }

    public function test_the_dashboard_is_told_who_is_looking_and_the_way_back(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Dani Admin']);
        $menuOwner = User::factory()->create();

        $this->actingAs($admin)->get(route('impersonate', $menuOwner->id));

        $this->getJson(route('api.user'))
            ->assertOk()
            ->assertJsonPath('data.email', $menuOwner->email)
            ->assertJsonPath('data.impersonation', [
                'admin' => 'Dani Admin',
                'leave_url' => route('impersonate.leave'),
            ]);
    }

    public function test_an_owner_signed_in_themself_sees_no_way_back(): void
    {
        $menuOwner = User::factory()->create();

        $this->actingAs($menuOwner)->getJson(route('api.user'))->assertJsonPath('data.impersonation', null);
    }
}
