<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The only thing Paddle sells: bundles of Qayema coins. Everything priced
     * in-app (templates now, slot packs later) is an integer of coins, so adding
     * a product never means touching the billing pipeline again.
     *
     * The price id is per Paddle environment; the cash amount itself lives in
     * Paddle and is edited from the admin panel.
     */
    public function up(): void
    {
        Schema::create('coin_packs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->unsignedInteger('coins');
            $table->string('paddle_price_id_sandbox')->nullable();
            $table->string('paddle_price_id_production')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_packs');
    }
};
