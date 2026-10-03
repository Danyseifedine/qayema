<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A guest may change an order placed in the menu until the restaurant
 * accepts it. When they last did, and how many times, so the owner notices.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('guest_updated_at')->nullable()->after('closed_at');
            $table->unsignedSmallInteger('guest_updates')->default(0)->after('guest_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['guest_updated_at', 'guest_updates']);
        });
    }
};
