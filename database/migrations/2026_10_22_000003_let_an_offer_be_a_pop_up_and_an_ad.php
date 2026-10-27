<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * An offer may now be a pop-up and a short ad together: the ad has its own
 * wait, counted from the pop-up closing (or from the menu opening for an
 * ad alone). An ad made before kept its wait in `delay_seconds`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->unsignedTinyInteger('ad_delay_seconds')->default(2)->after('delay_seconds');
        });

        DB::table('offers')->where('placements', 'like', '%"ad"%')->update(['ad_delay_seconds' => DB::raw('delay_seconds')]);
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('ad_delay_seconds');
        });
    }
};
