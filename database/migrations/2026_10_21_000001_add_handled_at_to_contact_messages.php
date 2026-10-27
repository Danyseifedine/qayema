<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When an admin dealt with a message, so the inbox shows what is still
 * open. Null: still open. Indexed with the date it came in, for the open
 * list and the menu's count.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->timestamp('handled_at')->nullable()->after('package_id');
            $table->index(['handled_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['handled_at', 'created_at']);
            $table->dropColumn('handled_at');
        });
    }
};
