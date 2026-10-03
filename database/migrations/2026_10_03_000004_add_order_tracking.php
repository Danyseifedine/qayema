<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A guest who ordered in the menu can follow the order on a page of its own.
 * Its address carries `tracking_token`, a long random string, so an order
 * cannot be found by guessing its short reference. The times say when the
 * restaurant accepted the order and when it was done or called off.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('tracking_token', 40)->nullable()->unique()->after('client_token');
            $table->timestamp('accepted_at')->nullable()->after('placed_at');
            $table->timestamp('closed_at')->nullable()->after('accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropUnique(['tracking_token']);
            $table->dropColumn(['tracking_token', 'accepted_at', 'closed_at']);
        });
    }
};
