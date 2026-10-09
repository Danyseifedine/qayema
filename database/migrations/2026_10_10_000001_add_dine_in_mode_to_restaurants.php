<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * How orders at the table reach the restaurant, apart from delivery and
 * pickup (`order_mode`): on the dashboard's Table orders page, as until now,
 * or on WhatsApp with the table's name at the top of the message.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('dine_in_mode', 16)->default('menu')->after('order_types');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('dine_in_mode');
        });
    }
};
