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
        Schema::table('menu_statistics', function (Blueprint $table) {
            $table->dropColumn(['country', 'interactions']);
        });
    }

    public function down(): void
    {
        Schema::table('menu_statistics', function (Blueprint $table) {
            $table->string('country', 2)->nullable()->after('referrer');
            $table->json('interactions')->nullable()->after('page_views');
        });
    }
};
