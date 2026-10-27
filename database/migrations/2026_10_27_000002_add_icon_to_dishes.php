<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A build's dish is named and drawn with an icon (one of CategoryIcons, the
 * same as a category's) above the menu's search, in place of a photo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->string('icon', 40)->nullable()->after('category_id');
        });
    }

    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->dropColumn('icon');
        });
    }
};
