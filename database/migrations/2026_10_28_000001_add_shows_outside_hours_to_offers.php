<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An offer may show on the menu all day while its deal works only in its
 * hours ("Happy hour, 5 PM to 7 PM", seen at noon): outside them a tap
 * says when it runs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->boolean('shows_outside_hours')->default(false)->after('time_windows');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('shows_outside_hours');
        });
    }
};
