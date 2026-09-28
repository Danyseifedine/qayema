<?php

namespace Tests\Feature\E2e;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The e2e server runs several PHP workers on one SQLite file. A transaction
 * that reads first and writes later (an order, a media row) must not fail
 * with "database is locked" because another worker wrote in between: in WAL
 * mode a deferred transaction's stale snapshot fails at once, without
 * waiting. Taking the write lock when the transaction begins avoids that.
 */
class SqliteTransactionModeTest extends TestCase
{
    private string $file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->file = tempnam(sys_get_temp_dir(), 'qayema-sqlite-');

        foreach (['first_worker', 'second_worker'] as $name) {
            config(["database.connections.{$name}" => [
                ...config('database.connections.sqlite'),
                'database' => $this->file,
                'journal_mode' => 'wal',
                'busy_timeout' => 50,
            ]]);
        }

        DB::connection('first_worker')->statement('create table rows (id integer primary key)');
    }

    protected function tearDown(): void
    {
        DB::purge('first_worker');
        DB::purge('second_worker');

        foreach ([$this->file, "{$this->file}-wal", "{$this->file}-shm"] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    public function test_a_transaction_that_reads_then_writes_is_not_undone_by_another_writer(): void
    {
        $first = DB::connection('first_worker');
        $second = DB::connection('second_worker');

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
