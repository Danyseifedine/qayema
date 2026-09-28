<?php

namespace App\Console\Commands;

use Database\Seeders\E2eSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Starts the end-to-end suite from nothing: a fresh e2e SQLite file, the
 * seed every spec builds on, and no media left from the last run.
 *
 * It wipes a database, so it refuses unless the app runs in the `e2e`
 * environment (APP_ENV=e2e, which loads .env.e2e) against a SQLite file named
 * e2e.sqlite. The local MySQL is out of its reach by construction.
 */
class E2eReset extends Command
{
    protected $signature = 'e2e:reset';

    protected $description = 'Reset the end-to-end test database and media (APP_ENV=e2e only)';

    public function handle(): int
    {
        $database = (string) config('database.connections.sqlite.database');

        if (! app()->environment('e2e') || config('database.default') !== 'sqlite' || basename($database) !== 'e2e.sqlite') {
            $this->error('Refusing: e2e:reset only runs with APP_ENV=e2e against database/e2e.sqlite.');

            return self::FAILURE;
        }

        File::delete([$database, $database.'-wal', $database.'-shm']);
        File::put($database, '');

        Storage::disk('e2e')->deleteDirectory('');
        File::deleteDirectory(storage_path('app/private/temp'));

        $this->call('migrate', ['--force' => true, '--seed' => true, '--seeder' => E2eSeeder::class]);

        $this->info('E2E database and media are fresh.');

        return self::SUCCESS;
    }
}
