<?php

namespace Tests\Feature\Console;

use App\Models\BlockedIp;
use App\Services\Health\HealthChecks;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * What routes/console.php runs in the background, so it ships with the code
 * rather than a crontab: the heartbeat `/up` checks, the queue worker, the
 * hourly menu clearing and the nightly pruning.
 */
class BackgroundWorkTest extends TestCase
{
    use RefreshDatabase;

    private function scheduled(string $command): ?Event
    {
        return collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => str_contains((string) $event->command, $command));
    }

    public function test_the_queue_worker_runs_through_each_minute_and_never_twice(): void
    {
        $worker = $this->scheduled('queue:work');

        $this->assertNotNull($worker, 'The queue worker is not on the schedule');
        $this->assertSame('* * * * *', $worker->expression);
        // It stays up for most of the minute: a job waits seconds, not a minute.
        $this->assertStringNotContainsString('--stop-when-empty', $worker->command);
        $this->assertStringContainsString('--max-time=55', $worker->command);
        $this->assertTrue($worker->withoutOverlapping);
        $this->assertTrue($worker->runInBackground);
    }

    public function test_the_heartbeat_beats_every_minute(): void
    {
        File::delete(HealthChecks::heartbeatFile());
        $heartbeat = collect(app(Schedule::class)->events())
            ->first(fn (Event $event): bool => $event instanceof CallbackEvent && $event->description === 'health:heartbeat');

        $this->assertNotNull($heartbeat);
        $this->assertSame('* * * * *', $heartbeat->expression);

        $heartbeat->run($this->app);

        $this->assertFileExists(HealthChecks::heartbeatFile());
        File::delete(HealthChecks::heartbeatFile());
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function nightlyAndHourly(): array
    {
        return [
            'menus whose package or grant changed' => ['menus:forget-ended', '0 * * * *'],
            'failed jobs after 30 days' => ['queue:prune-failed --hours=720', '30 3 * * *'],
            "the phone apps' expired tokens" => ['sanctum:prune-expired --hours=48', '35 3 * * *'],
            'ended blocks' => ['model:prune', '40 3 * * *'],
        ];
    }

    #[DataProvider('nightlyAndHourly')]
    public function test_it_is_on_the_schedule(string $command, string $expression): void
    {
        $event = $this->scheduled($command);

        $this->assertNotNull($event, "{$command} is not on the schedule");
        $this->assertSame($expression, $event->expression);
    }

    public function test_a_block_that_ended_a_week_ago_is_pruned_and_the_rest_kept(): void
    {
        $old = BlockedIp::query()->create(['ip' => '198.51.100.1', 'reason' => 'auto', 'expires_at' => now()->subDays(8)]);
        $recent = BlockedIp::query()->create(['ip' => '198.51.100.2', 'reason' => 'auto', 'expires_at' => now()->subDays(6)]);
        $active = BlockedIp::query()->create(['ip' => '198.51.100.3', 'reason' => 'auto', 'expires_at' => now()->addHour()]);
        $forever = BlockedIp::query()->create(['ip' => '198.51.100.4', 'reason' => 'admin', 'expires_at' => null]);

        $this->artisan('model:prune', ['--model' => [BlockedIp::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($recent);
        $this->assertModelExists($active);
        $this->assertModelExists($forever);
    }

    public function test_a_queue_on_redis_has_a_database_of_its_own(): void
    {
        $this->assertSame('queue', config('queue.connections.redis.connection'));
        $this->assertNotSame(config('database.redis.cache.database'), config('database.redis.queue.database'));
        $this->assertNotSame(config('database.redis.default.database'), config('database.redis.queue.database'));
    }
}
