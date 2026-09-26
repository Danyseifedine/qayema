<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An order a guest placed from the public menu.
     *
     * The total is stored rather than summed from the lines on read: the menu
     * it was ordered from can change afterwards, and what the guest agreed to
     * must not move with it. `reference` is the short code a guest and an
     * owner say to each other.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 12)->unique();
            $table->string('status', 16)->default('placed');
            $table->char('currency', 3);
            $table->decimal('total', 10, 2);
            $table->text('note')->nullable();
            $table->timestamp('placed_at');
            $table->timestamps();

            $table->index(['restaurant_id', 'placed_at']);
            $table->index(['restaurant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
