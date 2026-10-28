<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every read of a restaurant's orders over time names the channel too
 * (MenuOrderStats, the analytics' sales and counts: menu orders only), so
 * `(restaurant_id, placed_at)` becomes `(restaurant_id, channel, placed_at)`
 * and a WhatsApp-heavy restaurant's taps are no longer read and thrown away.
 * `(restaurant_id, status)` goes: every status filter also names the
 * channel, which `orders_pulse_index` (restaurant, channel, status, ...)
 * serves better. The new index comes first, so the restaurant's foreign key
 * always has one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['restaurant_id', 'channel', 'placed_at']);
            $table->dropIndex(['restaurant_id', 'placed_at']);
            $table->dropIndex(['restaurant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['restaurant_id', 'placed_at']);
            $table->index(['restaurant_id', 'status']);
            $table->dropIndex(['restaurant_id', 'channel', 'placed_at']);
        });
    }
};
