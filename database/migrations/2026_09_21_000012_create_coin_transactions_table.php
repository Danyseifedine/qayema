<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Append-only coin ledger — the source of truth for a balance.
     * `amount` is signed (credits positive, spends negative) and `balance_after`
     * snapshots the running total so history renders without re-summing.
     *
     * `reference` is the external id behind the row (the Paddle transaction for
     * a purchase, the template_purchases id for a spend) and is unique per type,
     * so a replayed webhook credits once.
     */
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->unsignedInteger('balance_after');
            $table->string('type', 16);
            $table->string('reference')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->unique(['type', 'reference']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
