<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The shape an offer takes where the owner may choose one: a banner is a
 * rectangle or a square, a pop-up a square or tall. An ad is always a
 * rectangle and the Offers section is shaped like a dish.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('banner_shape', 16)->default('rectangle')->after('placements');
            $table->string('popup_shape', 16)->default('square')->after('banner_shape');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['banner_shape', 'popup_shape']);
        });
    }
};
