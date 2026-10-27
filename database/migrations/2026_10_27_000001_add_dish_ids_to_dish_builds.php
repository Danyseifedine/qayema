<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A category's build may offer only some of its dishes (`dish_ids`, the
 * ones the owner ticked). Null offers every dish of the category, a dish
 * added later included.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dish_builds', function (Blueprint $table) {
            $table->json('dish_ids')->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('dish_builds', function (Blueprint $table) {
            $table->dropColumn('dish_ids');
        });
    }
};
