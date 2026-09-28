<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One restaurant per owner (unique user_id). `name` and `description` are
     * spatie/translatable JSON ({"ar": ..., "en": ...}); the owner only ever
     * edits the locale in `default_locale`.
     *
     * A new restaurant starts with template_id = null — the dashboard stays
     * locked until the owner picks one — and on the default package, which is
     * what its limits and features resolve from. `package_ends_at` is an
     * admin-set expiry: once it passes, the restaurant falls back to the
     * default package until a new one is assigned.
     */
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            // The package is in force from package_started_at (null = always
            // has been) until package_ends_at (null = forever).
            $table->timestamp('package_started_at')->nullable();
            $table->timestamp('package_ends_at')->nullable();

            $table->json('name');
            $table->json('description')->nullable();
            $table->string('slug')->unique();

            // Where the restaurant is, as a map link. There is no separate
            // written address: one link is what a guest taps for directions.
            $table->string('google_maps_url')->nullable();
            $table->char('country_code', 2)->nullable();
            $table->string('phone', 30)->nullable();

            // One range per weekday, keyed mon..sun, null for a day closed:
            // {"mon":{"open":"07:30","close":"22:00"},"tue":null,...}. The
            // timezone is what makes "open now" mean anything.
            $table->json('opening_hours')->nullable();
            $table->string('timezone', 64)->nullable();

            $table->char('currency', 3)->default('USD');
            // English is every menu's main language; `second_locale` is the one
            // other language the owner chose (config('locales.menu')), or null
            // for an English-only menu. `default_locale` is what the menu opens
            // in: 'en' or the second language.
            $table->char('default_locale', 2)->default('en');
            $table->char('second_locale', 2)->nullable();
            $table->boolean('is_active')->default(true);

            // The owner's colours, per design: {"<template_id>": {key: value}},
            // holding only what they changed from that design's defaults, so
            // each design keeps its own when the owner switches between them.
            $table->json('template_settings')->nullable();
            // The owner's font per writing system, {script: family}, from
            // config/fonts.php. The same for every design.
            $table->json('menu_fonts')->nullable();
            $table->json('qr_settings')->nullable();
            // Optional features the owner switched off on the dashboard's
            // Features page (Restaurant::OPTIONAL_FEATURES). Orders and
            // languages change the public menu too; nothing is deleted.
            $table->json('switched_off')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
