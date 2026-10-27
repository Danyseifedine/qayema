<?php

namespace Tests\Integration\Services\Health;

use App\Services\Health\HealthChecks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * App\Services\Health\HealthChecks: what `/up` checks. The database and the
 * cache everywhere; the scheduler, the queue and failed jobs in production,
 * the only place they run.
 */
class HealthChecksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        File::delete(HealthChecks::heartbeatFile());
    }

    protected function tearDown(): void
    {
        File::delete(HealthChecks::heartbeatFile());

        parent::tearDown();
    }

    private function inProduction(): void
    {
        $this->app['env'] = 'production';
        config(['queue.default' => 'database']);
    }

    /** A scheduler that last ran $secondsAgo seconds ago. */
    private function scheduleRan(int $secondsAgo): void
    {
        HealthChecks::beat();
        touch(HealthChecks::heartbeatFile(), time() - $secondsAgo);
    }

    private function queueJob(int $availableSecondsAgo): void
    {
        DB::table('jobs')->insert([
            'queue' => 'default',
            'payload' => '{}',
            'attempts' => 0,
            'reserved_at' => null,
            'available_at' => time() - $availableSecondsAgo,
            'created_at' => time() - $availableSecondsAgo,
        ]);
    }

    private function jobFailed(int $secondsAgo): void
    {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'RuntimeException: Cloudflare did not clear the menu cache.',
            'failed_at' => now()->subSeconds($secondsAgo),
        ]);
    }

    public function test_outside_production_only_the_database_and_cache_are_checked(): void
    {
        $this->queueJob(3600);
        $this->jobFailed(60);

        $this->assertSame([], app(HealthChecks::class)->failures());
    }

    public function test_in_production_all_is_well_with_a_recent_heartbeat_and_nothing_waiting(): void
    {
        $this->inProduction();
        $this->scheduleRan(30);
        $this->queueJob(60);
        $this->jobFailed(2 * 3600);

        $this->assertSame([], app(HealthChecks::class)->failures());
    }

    public function test_a_scheduler_that_never_ran_or_stopped_fails(): void
    {
        $this->inProduction();

        $this->assertSame(['schedule: it has never run (is the cron line for schedule:run there?)'], app(HealthChecks::class)->failures());

        $this->scheduleRan(HealthChecks::SCHEDULE_SECONDS + 60);

        $this->assertStringStartsWith('schedule: last ran', app(HealthChecks::class)->failures()[0]);
    }

    public function test_a_job_waiting_too_long_fails_but_a_delayed_one_does_not(): void
    {
        $this->inProduction();
        $this->scheduleRan(10);

        // A job put off ten minutes (a retry's backoff) is not waiting yet.
        $this->queueJob(-600);
        $this->assertSame([], app(HealthChecks::class)->failures());

        $this->queueJob(HealthChecks::QUEUE_SECONDS + 30);
        $this->assertStringStartsWith('queue: a job has waited', app(HealthChecks::class)->failures()[0]);
    }

    public function test_a_job_that_failed_in_the_last_hour_fails(): void
    {
        $this->inProduction();
        $this->scheduleRan(10);
        $this->jobFailed(600);
        $this->jobFailed(1200);

        $this->assertSame(['failed jobs: 2 in the last hour (php artisan queue:failed)'], app(HealthChecks::class)->failures());
    }

    public function test_a_database_that_does_not_answer_fails(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new \RuntimeException('SQLSTATE[HY000] [2002] Connection refused'));

        $this->assertSame(['database: SQLSTATE[HY000] [2002] Connection refused'], app(HealthChecks::class)->failures());
    }

    public function test_a_cache_that_loses_what_it_is_given_fails(): void
    {
        Cache::shouldReceive('put')->once();
        Cache::shouldReceive('get')->once()->andReturn(null);

        $this->assertSame(['cache: a value put in the cache did not come back'], app(HealthChecks::class)->failures());
    }

    public function test_every_problem_is_named_together(): void
    {
        $this->inProduction();
        Cache::shouldReceive('put')->once()->andThrow(new \RuntimeException('Connection refused [tcp://127.0.0.1:6379]'));

        $failures = app(HealthChecks::class)->failures();

        $this->assertSame('cache: Connection refused [tcp://127.0.0.1:6379]', $failures[0]);
        $this->assertStringStartsWith('schedule:', $failures[1]);
    }
}
