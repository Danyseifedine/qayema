<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An offer's own dish (its "deal dish", Offer::deal()): made from the offer
 * editor, on the menu only while that offer is live. One per offer; the
 * offer's delete removes the dish through the model (so its photo goes),
 * and the column is cleared if the offer row ever goes some other way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->foreignId('offer_id')->nullable()->after('category_id')->index()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('offer_id');
        });
    }
};
