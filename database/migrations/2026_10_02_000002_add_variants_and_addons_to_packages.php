<?php

use App\Services\Packages\Entitlements;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Variants and add-ons join Pro, Premium and Custom. Free reads them as 0
 * through App\Enums\Feature::defaultValue(), so it needs no key.
 */
return new class extends Migration
{
    private const INCLUDED = ['pro', 'premium', 'custom'];

    public function up(): void
    {
        $this->set(1);
    }

    public function down(): void
    {
        $this->set(null);
    }

    private function set(?int $value): void
    {
        foreach (DB::table('packages')->whereIn('slug', self::INCLUDED)->get() as $package) {
            $features = json_decode((string) $package->features, true) ?: [];

            foreach (['variants', 'addons'] as $flag) {
                if ($value === null) {
                    unset($features[$flag]);
                } else {
                    $features[$flag] = $value;
                }
            }

            DB::table('packages')->where('id', $package->id)->update(['features' => json_encode($features)]);
        }

        // Written past the model, so the cached entitlements are cleared here.
        Entitlements::flushAll();
    }
};
