<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A template unlocked with coins. Ownership is permanent, so an owner can
     * switch back and forth between everything they own without paying again;
     * the unique pair is what makes "already owned" a single indexed lookup.
     * `price_paid` records what it cost at the time, since the template's price
     * can change later.
     */
    public function up(): void
    {
        Schema::create('template_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coin_transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('price_paid');
            $table->timestamps();

            $table->unique(['restaurant_id', 'template_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_purchases');
    }
};
