<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurant_statistics', function (Blueprint $table) {
            $table->unsignedInteger('whatsapp_orders')->default(0)->after('page_views');
        });
    }

    public function down(): void
    {
        Schema::table('restaurant_statistics', function (Blueprint $table) {
            $table->dropColumn('whatsapp_orders');
        });
    }
};
