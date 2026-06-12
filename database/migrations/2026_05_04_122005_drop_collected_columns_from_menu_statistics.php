<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the ip_address index before the column. MySQL removes an index
        // implicitly with its column, but SQLite errors on the dangling index,
        // which breaks fresh migrations (e.g. the test database).
        if (Schema::hasIndex('menu_statistics', 'menu_statistics_ip_address_index')) {
            Schema::table('menu_statistics', function (Blueprint $table) {
                $table->dropIndex('menu_statistics_ip_address_index');
            });
        }

        Schema::table('menu_statistics', function (Blueprint $table) {
            $table->dropColumn(['ip_address', 'user_agent', 'referrer', 'left_at']);
        });
    }

    public function down(): void
    {
        Schema::table('menu_statistics', function (Blueprint $table) {
            $table->string('ip_address', 45)->nullable()->after('session_id');
            $table->text('user_agent')->nullable()->after('ip_address');
            $table->string('referrer')->nullable()->after('user_agent');
            $table->timestamp('left_at')->nullable()->after('viewed_at');
        });
    }
};
