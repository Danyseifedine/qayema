<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Limits now come from the package engine (limit-kind features on the
     * free template, overridable per restaurant) instead of columns.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['dish_limit', 'category_limit', 'social_link_limit']);
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->integer('dish_limit')->default(40);
            $table->integer('category_limit')->default(10);
            $table->integer('social_link_limit')->default(4);
        });
    }
};
