<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add-ons may sit in groups ("Sauces: pick up to 2"), each with the
     * fewest and most a guest picks from it. An add-on with no group is
     * picked freely, as before.
     */
    public function up(): void
    {
        Schema::create('dish_addon_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->unsignedSmallInteger('min_select')->default(0);
            // Null is no limit.
            $table->unsignedSmallInteger('max_select')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['dish_id', 'display_order']);
        });

        Schema::table('dish_addons', function (Blueprint $table) {
            $table->foreignId('dish_addon_group_id')->nullable()->after('dish_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dish_addons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dish_addon_group_id');
        });

        Schema::dropIfExists('dish_addon_groups');
    }
};
