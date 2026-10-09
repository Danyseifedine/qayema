<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A menu's main language is the restaurant's own choice now, not always
 * English: the one every name is required in, and the only one shown while
 * the second language is off or not on the package. Every existing menu was
 * written in English first, so they all start there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->char('main_locale', 2)->default('en')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('main_locale');
        });
    }
};
