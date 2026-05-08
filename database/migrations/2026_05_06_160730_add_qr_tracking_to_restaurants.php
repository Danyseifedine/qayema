<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->json('qr_settings')->nullable()->after('template_settings');
        });

        Schema::table('restaurant_statistics', function (Blueprint $table) {
            $table->boolean('via_qr')->default(false)->after('page_views');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('qr_settings');
        });

        Schema::table('restaurant_statistics', function (Blueprint $table) {
            $table->dropColumn('via_qr');
        });
    }
};
