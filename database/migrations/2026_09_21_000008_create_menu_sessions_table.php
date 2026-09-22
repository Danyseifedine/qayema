<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per public-menu visit, written by the public menu page and pruned
     * by `stats:rollup` after the retention window. QR scan counts come from
     * `via_qr`, set when the menu is opened from a QR link (?qr=1).
     */
    public function up(): void
    {
        Schema::create('menu_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('session_id', 64)->index();
            $table->string('device_type', 32)->nullable();
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->timestamp('viewed_at');
            $table->boolean('via_qr')->default(false);
            $table->timestamps();

            $table->index(['restaurant_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_sessions');
    }
};
