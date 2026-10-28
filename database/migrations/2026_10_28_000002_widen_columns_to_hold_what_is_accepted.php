<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Columns narrower than what the requests and forms accept, so a valid save
 * failed on MySQL ("Data too long", "Out of range") instead of being stored:
 *
 * - `order_items.full_price`: the line's price before the offer, which is
 *   never below `unit_price`, so it needs the same 15 digits that
 *   `2026_10_07_000001_widen_order_money_columns` gave the rest;
 * - `restaurant_social_links.url`: links of up to 500 characters are
 *   accepted (requests, admin form, dashboard);
 * - `restaurants.google_maps_url`: up to 2048 (a shared place link with its
 *   data part runs well past 255).
 *
 * Going down fails rather than cut a value that no longer fits.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('full_price', 15, 2)->nullable()->change();
        });

        Schema::table('restaurant_social_links', function (Blueprint $table) {
            $table->string('url', 500)->change();
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('google_maps_url', 2048)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('google_maps_url')->nullable()->change();
        });

        Schema::table('restaurant_social_links', function (Blueprint $table) {
            $table->string('url')->change();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('full_price', 10, 2)->nullable()->change();
        });
    }
};
