<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The admin panel's second gate. Filament's own Authenticate middleware runs
 * first (a guest is sent to /admin/login, a non-admin is refused by
 * canAccessPanel()), so this one is a backstop: it is exercised directly.
 */
class EnsureUserIsAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function handle(): Response
    {
        return (new EnsureUserIsAdmin)->handle(Request::create('/admin'), fn (): Response => new Response('panel'));
    }

    private function assertRefused(): void
    {
        try {
            $this->handle();
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
            $this->assertSame('Unauthorized access. Admin privileges required.', $e->getMessage());

            return;
        }

        $this->fail('The middleware let the request through.');
    }

    public function test_a_guest_is_refused(): void
    {
        $this->assertGuest();

        $this->assertRefused();
    }

    public function test_an_owner_is_refused(): void
    {
        $this->actingAs($this->owner()->user);

        $this->assertRefused();
    }

    public function test_an_admin_passes_through(): void
    {
        $this->actingAs($this->admin());

        $this->assertSame('panel', $this->handle()->getContent());
    }

    /** Through the panel: Filament stops the owner first, with the same status. */
    public function test_an_owner_is_forbidden_from_the_panel(): void
    {
        $this->actingAs($this->owner()->user)->get('/admin')->assertForbidden();
    }

    public function test_a_guest_is_sent_to_the_panel_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_an_admin_reaches_the_panel(): void
    {
        $this->actingAs($this->admin())->get('/admin')->assertOk();
    }
}
