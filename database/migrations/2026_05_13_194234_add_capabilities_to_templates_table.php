<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            // ── Restaurant Profile ────────────────────────────────────
            $table->boolean('has_logo')->default(true)->after('is_active');
            $table->boolean('has_cover_image')->default(true)->after('has_logo');
            $table->boolean('has_description')->default(true)->after('has_cover_image');
            $table->boolean('has_phone')->default(true)->after('has_description');
            $table->boolean('has_address')->default(true)->after('has_phone');
            $table->boolean('has_map')->default(true)->after('has_address');
            $table->boolean('has_schedule')->default(true)->after('has_map');
            $table->boolean('has_social_links')->default(true)->after('has_schedule');

            // ── Menu Content ──────────────────────────────────────────
            $table->boolean('has_dish_images')->default(true)->after('has_social_links');
            $table->boolean('has_dish_ingredients')->default(true)->after('has_dish_images');
            $table->boolean('has_dish_prices')->default(true)->after('has_dish_ingredients');
            $table->boolean('has_dish_tags')->default(true)->after('has_dish_prices');
            $table->boolean('has_category_images')->default(true)->after('has_dish_tags');
            $table->boolean('has_category_description')->default(true)->after('has_category_images');

            // ── UI / UX ───────────────────────────────────────────────
            $table->boolean('has_search')->default(true)->after('has_category_description');
            $table->boolean('has_search_title')->default(true)->after('has_search');
            $table->boolean('has_order_page_title')->default(true)->after('has_search_title');
            $table->boolean('has_final_price_show')->default(true)->after('has_order_page_title');
            $table->boolean('has_share_button')->default(true)->after('has_final_price_show');
            $table->boolean('has_qr_code')->default(true)->after('has_share_button');

            // ── Direction ─────────────────────────────────────────────
            $table->string('default_direction', 3)->default('ltr')->after('has_qr_code');
            $table->boolean('allows_direction_change')->default(true)->after('default_direction');
        });
    }

    public function down(): void
    {
        Schema::table('templates', function (Blueprint $table) {
            $table->dropColumn([
                'has_logo', 'has_cover_image', 'has_description', 'has_phone',
                'has_address', 'has_map', 'has_schedule', 'has_social_links',
                'has_dish_images', 'has_dish_ingredients', 'has_dish_prices', 'has_dish_tags',
                'has_category_images', 'has_category_description',
                'has_search', 'has_search_title', 'has_order_page_title', 'has_final_price_show',
                'has_share_button', 'has_qr_code',
                'default_direction', 'allows_direction_change',
            ]);
        });
    }
};
