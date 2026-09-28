<?php

namespace Tests\Integration\E2e;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\E2e\E2eServiceProvider;
use Tests\TestCase;

/**
 * What tests/E2e adds to the app under APP_ENV=e2e. The provider is
 * registered by hand here; nothing in this class touches the default
 * connection, which then points at the e2e file.
 */
class E2eServiceProviderTest extends TestCase
{
    private ?string $file = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(E2eServiceProvider::class);
    }

    protected function tearDown(): void
    {
        if ($this->file !== null) {
            DB::purge('first_worker');
            DB::purge('second_worker');

            foreach ([$this->file, "{$this->file}-wal", "{$this->file}-shm"] as $path) {
                if (is_file($path)) {
                    unlink($path);
                }
            }
        }

        parent::tearDown();
    }

    public function test_everything_it_writes_stays_under_the_e2e_testing_folder(): void
    {
        $root = storage_path('framework/testing/e2e');

        $this->assertSame($root, E2eServiceProvider::root());
        $this->assertSame("{$root}/database.sqlite", config('database.connections.sqlite.database'));
        $this->assertSame("{$root}/media", config('filesystems.disks.e2e.root'));
        $this->assertSame("{$root}/local", config('filesystems.disks.local.root'));
        $this->assertSame("{$root}/logs/e2e.log", config('logging.channels.single.path'));
        $this->assertSame(rtrim((string) config('app.url'), '/').'/__e2e/media', config('filesystems.disks.e2e.url'));
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
        config(['database.connections.sqlite.database' => ':memory:']);

        $this->artisan('e2e:reset')
            ->expectsOutputToContain('Refusing')
            ->assertFailed();
    }

    /**
     * The e2e server runs several PHP workers on one SQLite file. A
     * transaction that reads first and writes later (an order, a media row)
     * must not fail with "database is locked" because another worker wrote
     * in between: in WAL mode a deferred transaction's stale snapshot fails
     * at once, without waiting. Taking the write lock when the transaction
     * begins avoids that.
     */
    public function test_a_transaction_that_reads_then_writes_is_not_undone_by_another_writer(): void
    {
        $this->file = tempnam(sys_get_temp_dir(), 'qayema-sqlite-');
        foreach (['first_worker', 'second_worker'] as $name) {
            config(["database.connections.{$name}" => [
                ...config('database.connections.sqlite'),
                'database' => $this->file,
                'busy_timeout' => 50,
            ]]);
        }
        $first = DB::connection('first_worker');
        $second = DB::connection('second_worker');
        $first->statement('create table rows (id integer primary key)');

        $first->beginTransaction();
        $first->select('select count(*) from rows');

        // Another worker writes meanwhile. It waits for the lock the
        // transaction holds (here: gives up after its busy timeout) rather
        // than slipping in under it.
        try {
            $second->insert('insert into rows (id) values (1)');
        } catch (QueryException) {
            // Blocked while the first worker holds the write lock.
        }

        $first->insert('insert into rows (id) values (2)');
        $first->commit();

        $this->assertSame(1, $first->table('rows')->where('id', 2)->count());
    }
}
