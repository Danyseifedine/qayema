<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What the dashboard's writing help (the AI buttons) cost, a row per
 * restaurant per day of its own: the uses that gave the owner text, and the
 * tokens every call spent, the ones that missed the rules included
 * (App\Services\Ai\WritingHelpLog). The daily limit itself is counted apart
 * (WritingHelpUses); this is the record an admin reads.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('writing_help_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->date('day');
            $table->unsignedInteger('uses')->default(0);
            $table->unsignedBigInteger('input_tokens')->default(0);
            $table->unsignedBigInteger('output_tokens')->default(0);
            $table->timestamps();

            $table->unique(['restaurant_id', 'day']);
            $table->index('day');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('writing_help_days');
    }
};
