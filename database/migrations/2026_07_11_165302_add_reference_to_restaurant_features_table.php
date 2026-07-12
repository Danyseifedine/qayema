<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * External reference for a grant (e.g. the Paddle transaction id that paid
     * for it). Doubles as the idempotency key so a re-delivered webhook can't
     * grant the same purchase twice.
     */
    public function up(): void
    {
        Schema::table('restaurant_features', function (Blueprint $table) {
            $table->string('reference')->nullable()->after('source');
            $table->index(['restaurant_id', 'feature_id', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_features', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id', 'feature_id', 'reference']);
            $table->dropColumn('reference');
        });
    }
};
