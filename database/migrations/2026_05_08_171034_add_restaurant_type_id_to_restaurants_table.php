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
        Schema::table('restaurants', function (Blueprint $table) {
            $table->foreignId('restaurant_type_id')
                ->nullable()
                ->after('id')
                ->constrained('restaurant_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropForeign(['restaurant_type_id']);
            $table->dropColumn('restaurant_type_id');
        });
    }
};
