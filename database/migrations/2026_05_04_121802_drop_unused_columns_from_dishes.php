<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->dropColumn(['allergens', 'prep_time', 'serving_size']);
        });
    }

    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->json('allergens')->nullable()->after('ingredients');
            $table->integer('prep_time')->nullable()->after('display_order');
            $table->string('serving_size')->nullable()->after('prep_time');
        });
    }
};
