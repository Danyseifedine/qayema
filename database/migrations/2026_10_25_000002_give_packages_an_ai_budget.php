<?php

use App\Services\Packages\Entitlements;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What a package gives a restaurant of AI a month, in US cents (null for no
 * cap): the admin sets it in dollars, owners see it as credits
 * (App\Services\Ai\WritingHelpCredits), never as money. The four packages
 * start from config('package.catalog').
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('ai_budget_cents', 10, 4)->nullable()->after('features');
        });

        foreach ((array) config('package.catalog') as $package) {
            DB::table('packages')
                ->where('slug', $package['slug'])
                ->update(['ai_budget_cents' => $package['ai_budget_cents'] ?? null]);
        }

        Entitlements::flushAll();
    }

    public function down(): void
    {
        Schema::table('packages', fn (Blueprint $table) => $table->dropColumn('ai_budget_cents'));
    }
};
