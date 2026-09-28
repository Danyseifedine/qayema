<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menu layouts. A template is a row + a Blade view of the same slug — adding
     * one never touches PHP. Every active template is free to every restaurant;
     * `settings_schema` declares exactly which knobs the owner may turn, so
     * "this design can change its colors, that one is fixed" is data, not code.
     *
     * settings_schema shape:
     *   [{"key": "primary", "type": "color", "default": "#c8a45c", "label": {...}}]
     */
    public function up(): void
    {
        Schema::create('templates', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('settings_schema')->nullable();
            $table->boolean('is_active')->default(true);
            // Only packages with the premium_designs flag may use it.
            $table->boolean('is_premium')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
