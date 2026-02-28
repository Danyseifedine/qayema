<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_impersonate_menu_owner_and_redirects_to_dashboard(): void
    {
        $admin = User::factory()->admin()->create();
        $menuOwner = User::factory()->create();

        $response = $this->actingAs($admin)->get(route('impersonate', $menuOwner->id));

        $response->assertRedirect('/dashboard');
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

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($admin);
    }
}
