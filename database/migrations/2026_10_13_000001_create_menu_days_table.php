<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A menu's analytics for one of its own days, added up from the raw visits
 * and guest actions (App\Services\Analytics\MenuDays) before `stats:rollup`
 * deletes those after 6 months. The analytics read these rows for anything
 * older, so "All time" keeps the whole history. Visitors are counted with
 * HyperLogLog sketches (App\Support\HyperLogLog), which add up across days
 * without counting a returning guest twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            // The restaurant's own day, in its timezone when it was added up.
            $table->date('day');
            $table->unsignedInteger('views')->default(0);
            $table->unsignedInteger('qr_scans')->default(0);
            $table->binary('visitors')->nullable();
            // Visitors who put something in the cart (the funnel's middle step).
            $table->binary('carted')->nullable();
            // Views per local hour, 0 to 23.
            $table->json('hours');
            // {key: count}: the menu language, device type, browser, system.
            $table->json('languages');
            $table->json('devices');
            $table->json('browsers');
            $table->json('systems');
            // {event type: count}, {dish id: count}, {category id: count}, {term: count}.
            $table->json('actions');
            $table->json('dishes_added');
            $table->json('categories_opened');
            $table->json('searches');
            $table->json('missed_searches');
            $table->timestamps();

            $table->unique(['restaurant_id', 'day']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_days');
    }
};
