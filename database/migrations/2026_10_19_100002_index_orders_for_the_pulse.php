<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The dashboard's order numbers, read from the index alone: OrderPulse
 * (a restaurant's menu orders: the newest id, the last guest change, and
 * those placed by kind, on every new order and once a minute per dashboard
 * that cannot hear Pusher) and the orders list's `open` count (restaurant,
 * channel and status, then the kind). It starts with the columns of the one
 * it replaces, so nothing that used that one loses it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['restaurant_id', 'channel', 'status', 'fulfilment', 'guest_updated_at'], 'orders_pulse_index');
            $table->dropIndex(['restaurant_id', 'channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['restaurant_id', 'channel', 'status']);
            $table->dropIndex('orders_pulse_index');
        });
    }
};
