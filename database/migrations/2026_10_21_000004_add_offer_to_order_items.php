<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A line bought on an offer keeps which offer and the dish's price before
 * it, so the owner sees what the discount gave. Both copies, like the rest
 * of the line: a deleted offer leaves the price as it was paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('offer_id')->nullable()->after('dish_id')->constrained()->nullOnDelete();
            $table->string('offer_title')->nullable()->after('offer_id');
            $table->decimal('full_price', 10, 2)->nullable()->after('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offer_id');
            $table->dropColumn(['offer_title', 'full_price']);
        });
    }
};
