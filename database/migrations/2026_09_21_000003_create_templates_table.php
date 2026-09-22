<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menu layouts. A template is a row + a Blade view of the same slug — adding
     * one never touches PHP: the price is an integer in coins (0 = free) and
     * `settings_schema` declares exactly which knobs the owner may turn, so the
     * "free template can change some colors, paid ones can't" rule (or the
     * reverse) is data, not code.
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
            $table->unsignedInteger('price')->default(0);
            $table->json('settings_schema')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('templates');
    }
};
