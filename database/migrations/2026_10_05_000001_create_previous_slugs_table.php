<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A restaurant's former menu links. Printed QR codes, shared links and search
 * results keep pointing at an old link, so each one forwards to the
 * restaurant's link of today, and no other restaurant may take it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('previous_slugs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 255)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('previous_slugs');
    }
};
