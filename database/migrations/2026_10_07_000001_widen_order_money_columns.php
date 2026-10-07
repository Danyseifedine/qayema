<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Room for a menu in Lebanese pounds. A dish, an option and an add-on may
 * each cost up to 99,999,999.99, so a line (a dish with its choices, times
 * up to 99) and an order's total can run far past the 10 digits these
 * columns held: an LBP 2,000,000 dish times 60 no longer fits in them, and
 * the order would fail to save. Fifteen digits hold any real order.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total', 15, 2)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->change();
            $table->decimal('line_total', 15, 2)->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('total', 10, 2)->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('unit_price', 10, 2)->change();
            $table->decimal('line_total', 10, 2)->change();
        });
    }
};
