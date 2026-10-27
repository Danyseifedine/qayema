<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The metal of a package's crown in the dashboard (App\Enums\PackageTier),
 * once read from the four fixed slugs; now the admin's choice, so a package
 * made in the admin wears the right one. The four start from
 * config('package.catalog').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('tier', 10)->default('bronze')->after('is_featured');
        });

        foreach ((array) config('package.catalog') as $package) {
            DB::table('packages')->where('slug', $package['slug'])->update(['tier' => $package['tier'] ?? 'bronze']);
        }
    }

    public function down(): void
    {
        Schema::table('packages', fn (Blueprint $table) => $table->dropColumn('tier'));
    }
};
