<?php

namespace Tests\Feature\E2e;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The end-to-end helpers can wipe a database and sign anyone in, so outside
 * APP_ENV=e2e they must not exist at all: bootstrap/app.php only loads
 * tests/E2e there.
 */
class E2eGuardTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_e2e_routes_do_not_exist_outside_the_e2e_environment(): void
    {
        $owner = $this->owner();

        foreach (['scenario', 'login', 'package', 'password-reset-token'] as $endpoint) {
            $this->postJson('/__e2e/'.$endpoint, ['email' => $owner->user->email])->assertNotFound();
        }
        $this->get('/__e2e/media/1/logo.png')->assertNotFound();

        $this->assertGuest();
    }

    public function test_the_reset_command_does_not_exist_outside_the_e2e_environment(): void
    {
        $this->assertArrayNotHasKey('e2e:reset', Artisan::all());
    }

    public function test_the_app_reads_the_root_env_file_outside_the_e2e_environment(): void
    {
        $this->assertSame(base_path(), $this->app->environmentPath());
    }
}
