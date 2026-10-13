<?php

namespace App\Services\Health;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * What `/up` checks before it says the app is up (Laravel fires
 * DiagnosingHealth there; AppServiceProvider listens): the database answers,
 * the cache keeps a value, and in production the scheduler ran in the last
 * few minutes, no job has waited long and none failed in the last hour.
 * Anything wrong is thrown, which Laravel reports (the log, Grafana) and
 * answers with a 500, so an uptime check on `/up` sees it.
 *
 * The scheduler's heartbeat is a file it touches every minute (routes/
 * console.php), not a cache key: clearing the cache must not look like a
 * stopped scheduler.
 */
class HealthChecks
{
    /** The scheduler runs every minute; three missed runs is a stopped cron. */
    public const SCHEDULE_SECONDS = 180;

    /** The worker starts every minute; a job waiting longer than this is stuck. */
    public const QUEUE_SECONDS = 300;

    /** A failed job keeps `/up` failing this long (an alert, then it clears). */
    public const FAILED_JOB_SECONDS = 3600;

    public function __invoke(DiagnosingHealth $event): void
    {
        $failures = $this->failures();

        if ($failures !== []) {
            throw new RuntimeException('Health check failed: '.implode('; ', $failures));
        }
    }

    /** @return list<string> what is wrong, one line each; empty when all is well */
    public function failures(): array
    {
        $checks = [
            'database' => fn (): ?string => $this->database(),
            'cache' => fn (): ?string => $this->cache(),
        ];

        // Only production runs the scheduler and the worker (locally and in
        // the e2e suite, /up must answer without them).
        if (app()->isProduction()) {
            $checks += [
                'schedule' => fn (): ?string => $this->schedule(),
                'queue' => fn (): ?string => $this->queue(),
                'failed jobs' => fn (): ?string => $this->failedJobs(),
            ];
        }

        $failures = [];

        foreach ($checks as $name => $check) {
            try {
                $problem = $check();
            } catch (Throwable $exception) {
                $problem = Str::limit($exception->getMessage(), 200);
            }

            if ($problem !== null) {
                $failures[] = "{$name}: {$problem}";
            }
        }

        return $failures;
    }

    /** Called by the scheduler every minute. */
    public static function beat(): void
    {
        touch(self::heartbeatFile());
    }

    public static function heartbeatFile(): string
    {
        return storage_path('framework/schedule-heartbeat');
    }

    private function database(): ?string
    {
        DB::select('select 1');

        return null;
    }

    private function cache(): ?string
    {
        $value = Str::random(12);
        Cache::put('health:probe', $value, 10);

        return Cache::get('health:probe') === $value ? null : 'a value put in the cache did not come back';
    }

    private function schedule(): ?string
    {
        clearstatcache(true, self::heartbeatFile());
        $last = @filemtime(self::heartbeatFile());

        if ($last === false) {
            return 'it has never run (is the cron line for schedule:run there?)';
        }

        $ago = time() - $last;

        return $ago > self::SCHEDULE_SECONDS ? "last ran {$ago} s ago" : null;
    }

    private function queue(): ?string
    {
        $oldest = Queue::connection()->creationTimeOfOldestPendingJob();

        if ($oldest === null) {
            return null;
        }

        $waiting = time() - (int) $oldest;

        return $waiting > self::QUEUE_SECONDS ? "a job has waited {$waiting} s (is the worker running?)" : null;
    }

    private function failedJobs(): ?string
    {
        $failed = DB::connection(config('queue.failed.database'))
            ->table((string) config('queue.failed.table'))
            ->where('failed_at', '>=', now()->subSeconds(self::FAILED_JOB_SECONDS))
            ->count();

        return $failed > 0 ? "{$failed} in the last hour (php artisan queue:failed)" : null;
    }
}
