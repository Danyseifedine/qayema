<?php

namespace Tests\Feature\Security;

use App\Providers\AppServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Telescope\TelescopeServiceProvider;
use Tests\TestCase;

/**
 * AppServiceProvider::register() loads Telescope in the local environment
 * only, so no other environment can ever expose /telescope.
 */
class TelescopeRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_is_not_loaded_outside_local(): void
    {
        $this->assertSame('testing', $this->app->environment());

        $this->assertNull($this->app->getProvider(TelescopeServiceProvider::class));
        $this->get('/telescope')->assertNotFound();
    }

    public function test_production_does_not_load_it_either(): void
    {
        $this->app['env'] = 'production';

        (new AppServiceProvider($this->app))->register();

        $this->assertNull($this->app->getProvider(TelescopeServiceProvider::class));
    }

    public function test_local_loads_the_package(): void
    {
        $this->app['env'] = 'local';

        (new AppServiceProvider($this->app))->register();

        $this->assertInstanceOf(TelescopeServiceProvider::class, $this->app->getProvider(TelescopeServiceProvider::class));
    }
}
