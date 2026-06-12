<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * restaurant_statistics becomes menu_sessions (raw visit rows, pruned
     * after ~6 months by a scheduled job); restaurant_stats_daily holds the
     * nightly rollup that dashboards read, bucketed per restaurant timezone.
     */
    public function up(): void
    {
        Schema::rename('restaurant_statistics', 'menu_sessions');

        Schema::create('restaurant_stats_daily', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('visits')->default(0);
            $table->integer('unique_sessions')->default(0);
            $table->integer('qr_visits')->default(0);
            $table->integer('whatsapp_orders')->default(0);
            $table->integer('avg_time_spent')->nullable();
            $table->timestamps();

            $table->unique(['restaurant_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_stats_daily');
        Schema::rename('menu_sessions', 'restaurant_statistics');
    }
};
