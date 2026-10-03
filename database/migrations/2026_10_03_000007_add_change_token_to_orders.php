<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The page's own id for the last change a guest sent to an order, so the
 * same change sent twice (an answer lost on mobile data, a second tap) is
 * made once: "Add to order" must never add the same dishes twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('change_token', 36)->nullable()->after('guest_updates');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('change_token');
        });
    }
};
