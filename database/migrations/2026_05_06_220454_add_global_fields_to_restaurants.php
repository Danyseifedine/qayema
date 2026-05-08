<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->text('address')->nullable()->after('phone');
            $table->string('google_maps_url')->nullable()->after('address');
            $table->string('currency', 10)->nullable()->default('USD')->after('google_maps_url');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['address', 'google_maps_url', 'currency']);
        });
    }
};
