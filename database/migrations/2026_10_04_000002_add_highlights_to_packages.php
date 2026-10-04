<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A package card's own lines, written by the admin in each language
 * ({en: [...], ar: [...]}). Empty, the card lists what the package adds to
 * the one before it, worked out from its features. A package with lines in
 * config/package.php (Custom) gets them, unless an admin already wrote some.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->json('highlights')->nullable()->after('description');
        });

        foreach (config('package.catalog', []) as $package) {
            if (! empty($package['highlights'])) {
                DB::table('packages')
                    ->where('slug', $package['slug'])
                    ->whereNull('highlights')
                    ->update(['highlights' => json_encode($package['highlights'], JSON_UNESCAPED_UNICODE)]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('highlights');
        });
    }
};
