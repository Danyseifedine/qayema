<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Build your own" on a dish (App\Models\DishBuild): the guest fills a number
 * of parts the owner allows, each from a list of choices. A `split` build
 * cuts one item into parts (half Margherita, half Pepperoni); a `pieces`
 * build is that many whole items (three scoops). The choices are a
 * category's dishes (`category_id`, their prices followed) or the owner's
 * own list (dish_build_choices without a dish). A choice may carry its own
 * price for a part size (`part_prices`, {"2": "7.00"}); a size without one
 * pays its share of the whole (App\Services\Menu\BuildPrice).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_builds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->string('shape', 10);
            // The part counts the guest may pick, sorted: [1, 2, 3].
            $table->json('parts');
            // Its choices are this category's dishes; null: its own list.
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['dish_id', 'display_order']);
        });

        Schema::create('dish_build_choices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_build_id')->constrained()->cascadeOnDelete();
            // A category's dish this row prices by part size; null: one of
            // the build's own choices, named and priced here.
            $table->foreignId('dish_id')->nullable()->constrained()->cascadeOnDelete();
            $table->json('name')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->json('part_prices')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['dish_build_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_build_choices');
        Schema::dropIfExists('dish_builds');
    }
};
