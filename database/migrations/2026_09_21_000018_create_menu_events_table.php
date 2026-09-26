<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What guests do on a public menu once it is open: add a dish, open a
     * category, search, tap WhatsApp or the map. One row per action, sent in
     * small batches by the menu page, pruned with `menu_sessions` by
     * `stats:rollup`. Anonymous: `session_id` is the same session key the
     * visit was recorded under, and nothing identifies the guest.
     *
     * The dish and category go null rather than cascading, so deleting a dish
     * does not rewrite how busy the menu was.
     */
    public function up(): void
    {
        Schema::create('menu_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 64);
            $table->string('type', 32);
            $table->foreignId('dish_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            // A search term, a social platform, or a language code.
            $table->string('value', 64)->nullable();
            $table->timestamp('occurred_at');

            $table->index(['restaurant_id', 'occurred_at']);
            $table->index(['restaurant_id', 'type', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_events');
    }
};
