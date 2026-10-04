<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The two busiest tables, read the way they are asked:
 *
 * - menu_sessions by restaurant and time, counting QR visits and distinct
 *   sessions (the analytics, the QR page, the admin's restaurant list): the
 *   index carries `via_qr` and `session_id` too, so the counts are read from
 *   it without the rows. It starts like the one it replaces, so nothing that
 *   used that one loses it.
 * - both by time alone, for the nightly prune (`stats:rollup`), which would
 *   otherwise read every row of each table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_sessions', function (Blueprint $table) {
            $table->index(['restaurant_id', 'viewed_at', 'via_qr', 'session_id'], 'menu_sessions_counting_index');
            $table->dropIndex(['restaurant_id', 'viewed_at']);
            $table->index('viewed_at');
        });

        Schema::table('menu_events', function (Blueprint $table) {
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::table('menu_events', function (Blueprint $table) {
            $table->dropIndex(['occurred_at']);
        });

        Schema::table('menu_sessions', function (Blueprint $table) {
            $table->dropIndex(['viewed_at']);
            $table->index(['restaurant_id', 'viewed_at']);
            $table->dropIndex('menu_sessions_counting_index');
        });
    }
};
