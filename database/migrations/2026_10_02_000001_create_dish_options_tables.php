<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A dish's variants (Size, Spice level: the guest picks one option of
     * each, and the option adds its price to the dish's) and add-ons (Extra
     * cheese: any number, each adds its price). An order line keeps what was
     * chosen as a snapshot in the guest's language.
     */
    public function up(): void
    {
        Schema::create('dish_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['dish_id', 'display_order']);
        });

        Schema::create('dish_variant_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_variant_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            // Added to the dish's price; 0 for a choice that costs nothing more.
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['dish_variant_id', 'display_order']);
        });

        Schema::create('dish_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->json('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();

            $table->index(['dish_id', 'display_order']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->json('options')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('options');
        });

        Schema::dropIfExists('dish_addons');
        Schema::dropIfExists('dish_variant_options');
        Schema::dropIfExists('dish_variants');
    }
};
