<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An owner's offer on the menu (Premium): what it says, where it shows
 * (a banner, the Offers section, a pop-up or a short ad), when (a window of
 * dates, a repeat and times of day, in the restaurant's timezone), and an
 * optional discount on the dishes it names (dish_offer).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->json('title');
            $table->json('description')->nullable();

            // banner, section, popup or ad (popup and ad never together).
            $table->json('placements');
            // What a tap does: nothing, the dishes, or a link.
            $table->string('action', 16)->default('none');
            $table->string('action_url', 500)->nullable();

            // Pop-up and ad only: how often one guest sees it, after how
            // long, and how long an ad stays.
            $table->string('show_again', 16)->default('every_visit');
            $table->unsignedTinyInteger('delay_seconds')->default(0);
            $table->unsignedTinyInteger('ad_seconds')->default(6);

            $table->string('discount_type', 16)->default('none');
            $table->decimal('discount_value', 10, 2)->nullable();

            // When: inside [starts_at, ends_at) (UTC; null is open-ended),
            // on the days the repeat names, within the times of day (null:
            // all day). App\Services\Offers\OfferSchedule reads it.
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('repeat', 16)->default('always');
            $table->json('weekdays')->nullable();
            $table->json('month_days')->nullable();
            $table->json('time_windows')->nullable();

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['restaurant_id', 'display_order']);
            $table->index('starts_at');
            $table->index('ends_at');
        });

        Schema::create('dish_offer', function (Blueprint $table) {
            $table->foreignId('offer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();

            $table->primary(['offer_id', 'dish_id']);
            $table->index('dish_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_offer');
        Schema::dropIfExists('offers');
    }
};
