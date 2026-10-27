<?php

use App\Services\Packages\Entitlements;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The writing help is held to its credits a month alone: the uses a day
 * (`writing_help_limit`) are gone, from every package and every grant.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('packages')->get() as $package) {
            $features = json_decode((string) $package->features, true) ?: [];

            if (array_key_exists('writing_help_limit', $features)) {
                unset($features['writing_help_limit']);
                DB::table('packages')->where('id', $package->id)->update(['features' => json_encode($features)]);
            }
        }

        DB::table('feature_grants')->where('feature', 'writing_help_limit')->delete();

        Entitlements::flushAll();
    }

    public function down(): void
    {
        // The uses a day are not brought back: a package without the key
        // would read the old default, so nothing to undo.
    }
};
