<?php

namespace Tests\E2e;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Everything the Playwright suite (../qayema-dashboard/e2e) needs from this
 * app, registered by bootstrap/app.php only when APP_ENV=e2e, which also
 * makes Laravel read tests/E2e/.env.e2e instead of the root .env.
 *
 * Whatever the e2e server writes lands under storage/framework/testing/e2e:
 * the SQLite database, uploaded media, temp uploads and the log. Nothing
 * reaches the MySQL database or the real storage folders.
 */
class E2eServiceProvider extends ServiceProvider
{
    public static function root(string $path = ''): string
    {
        return storage_path('framework/testing/e2e'.($path === '' ? '' : '/'.$path));
    }

    public static function databasePath(): string
    {
        return self::root('database.sqlite');
    }

    public function register(): void
    {
        config([
            // Several server workers share one file: wait for a lock rather
            // than fail, let readers run beside a writer, and take the write
            // lock when a transaction begins: in WAL mode a transaction that
            // read first fails at once ("database is locked") if another
            // worker committed in between.
            'database.connections.sqlite' => [
                ...config('database.connections.sqlite'),
                'database' => self::databasePath(),
                'busy_timeout' => 10_000,
                'journal_mode' => 'wal',
                'transaction_mode' => 'IMMEDIATE',
            ],
            // MEDIA_DISK=e2e: uploaded images, served by routes.php.
            'filesystems.disks.e2e' => [
                'driver' => 'local',
                'root' => self::root('media'),
                'url' => rtrim((string) config('app.url'), '/').'/__e2e/media',
                'visibility' => 'public',
                'throw' => false,
                'report' => false,
            ],
            // Temp uploads (MediaService::tempRoot()) and the log.
            'filesystems.disks.local.root' => self::root('local'),
            'logging.channels.single.path' => self::root('logs/e2e.log'),
        ]);
    }

    public function boot(): void
    {
        Route::middleware('web')->group(__DIR__.'/routes.php');

        $this->commands([E2eResetCommand::class]);
    }
}
