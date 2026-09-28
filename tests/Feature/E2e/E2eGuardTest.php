<?php

namespace Tests\Feature\E2e;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * The end-to-end helpers can wipe a database and sign anyone in, so they must
 * not exist outside APP_ENV=e2e.
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

        $this->assertGuest();
    }

    public function test_the_reset_refuses_outside_the_e2e_environment(): void
    {
        $this->artisan('e2e:reset')
            ->expectsOutputToContain('Refusing')
            ->assertFailed();
    }

    public function test_the_reset_refuses_a_database_that_is_not_the_e2e_file_even_in_e2e(): void
    {
        $this->app['env'] = 'e2e';

        $this->artisan('e2e:reset')
            ->expectsOutputToContain('Refusing')
            ->assertFailed();
    }
}
