<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ordering in the menu, beside ordering on WhatsApp.
 *
 * A restaurant picks one way (`order_mode`) and, in the menu, which kinds of
 * order it accepts (`order_types`, null meaning all). An order remembers the
 * way it came in (`channel`): every order before this one was a tap that
 * opened WhatsApp, so the default keeps them as that. An order placed in the
 * menu carries what the restaurant needs to deliver it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('order_mode', 16)->default('whatsapp')->after('switched_off');
            $table->json('order_types')->nullable()->after('order_mode');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('channel', 16)->default('whatsapp')->after('status');
            $table->string('fulfilment', 16)->nullable()->after('channel');
            $table->string('guest_phone', 32)->nullable()->after('note');
            $table->text('address')->nullable()->after('guest_phone');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            // The page's own id for one press of "Place order": a retry or a
            // double tap sends it again and gets the same order back.
            $table->string('client_token', 36)->nullable()->after('longitude');

            $table->unique(['restaurant_id', 'client_token']);
            $table->index(['restaurant_id', 'channel', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['restaurant_id', 'client_token']);
            $table->dropIndex(['restaurant_id', 'channel', 'status']);
            $table->dropColumn(['channel', 'fulfilment', 'guest_phone', 'address', 'latitude', 'longitude', 'client_token']);
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['order_mode', 'order_types']);
        });
    }
};
