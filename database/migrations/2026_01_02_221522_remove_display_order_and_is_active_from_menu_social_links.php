<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menu_social_links', function (Blueprint $table) {
            $table->dropColumn(['display_order', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_social_links', function (Blueprint $table) {
            $table->integer('display_order')->default(0)->after('url');
            $table->boolean('is_active')->default(true)->after('display_order');
        });
    }
};
