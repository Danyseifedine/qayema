<?php

namespace Tests\E2e;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Starts the end-to-end suite from nothing: a fresh SQLite file, the seed
 * every spec builds on, and no media, temp uploads or log left from the
 * last run — everything under storage/framework/testing/e2e.
 *
 * Registered only in the e2e environment, and it still refuses unless the
 * connection is the e2e SQLite file: the local MySQL is out of its reach.
 */
class E2eResetCommand extends Command
{
    protected $signature = 'e2e:reset';

    protected $description = 'Reset the end-to-end test database and media';

    public function handle(): int
    {
        $database = (string) config('database.connections.sqlite.database');

        if (! app()->environment('e2e') || config('database.default') !== 'sqlite' || $database !== E2eServiceProvider::databasePath()) {
            $this->error('Refusing: e2e:reset only runs with APP_ENV=e2e against '.E2eServiceProvider::databasePath().'.');

            return self::FAILURE;
        }

        File::deleteDirectory(E2eServiceProvider::root());
        File::ensureDirectoryExists(dirname($database));
        File::put($database, '');

        $this->call('migrate', ['--force' => true, '--seed' => true, '--seeder' => E2eSeeder::class]);

        $this->info('E2E database and media are fresh.');

        return self::SUCCESS;
    }
}
