<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every read of `menu_sessions.session_id` names the restaurant too (the
 * analytics, the rollup, the admin's lists), so `menu_sessions_counting_index`
 * (restaurant_id, viewed_at, via_qr, session_id) serves them, and the visit
 * recorder only inserts. The index on session_id alone was only a cost on
 * every visit written.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_sessions', function (Blueprint $table) {
            $table->dropIndex(['session_id']);
        });
    }

    public function down(): void
    {
        Schema::table('menu_sessions', function (Blueprint $table) {
            $table->index('session_id');
        });
    }
};
