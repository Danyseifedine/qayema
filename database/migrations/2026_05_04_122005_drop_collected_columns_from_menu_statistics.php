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
