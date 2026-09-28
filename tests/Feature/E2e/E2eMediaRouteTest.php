<?php

namespace Tests\Feature\E2e;

use Illuminate\Support\Facades\Storage;
use Tests\E2e\E2eServiceProvider;
use Tests\TestCase;

/**
 * Under APP_ENV=e2e uploaded media is served by tests/E2e/routes.php rather
 * than the public/storage link, so the suite never writes to real storage.
 */
class E2eMediaRouteTest extends TestCase
{
    public function test_the_media_route_serves_what_the_e2e_disk_holds(): void
    {
        $this->app->register(E2eServiceProvider::class);
        Storage::fake('e2e');
        Storage::disk('e2e')->put('7/logo.png', 'png');

        $this->get('/__e2e/media/7/logo.png')->assertOk();
        $this->get('/__e2e/media/7/missing.png')->assertNotFound();
    }
}
