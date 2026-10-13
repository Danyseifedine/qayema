<?php

namespace Tests\Feature\Health;

use App\Services\Health\HealthChecks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Tests\TestCase;

/**
 * `GET /up`, what an uptime check (and deploy.sh) asks: 200 only when
 * HealthChecks finds nothing wrong; otherwise a 500, and the reason is
 * reported to the log (Grafana).
 */
class UpTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::delete(HealthChecks::heartbeatFile());

        parent::tearDown();
    }

    public function test_up_when_all_is_well(): void
    {
        $this->get('/up')->assertOk();
    }

    public function test_down_and_reported_when_a_check_fails(): void
    {
        config(['app.debug' => false]);
        Exceptions::fake();
        $this->app['env'] = 'production';
        File::delete(HealthChecks::heartbeatFile());

        $this->get('/up')->assertServerError();

        Exceptions::assertReported(fn (RuntimeException $exception): bool => str_contains($exception->getMessage(), 'Health check failed: schedule: it has never run'));
    }

    public function test_up_again_once_the_scheduler_beats(): void
    {
        $this->app['env'] = 'production';
        HealthChecks::beat();

        $this->get('/up')->assertOk();
    }
}
