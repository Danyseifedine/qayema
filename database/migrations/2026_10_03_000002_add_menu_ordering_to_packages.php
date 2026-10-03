<?php

use App\Services\Packages\Entitlements;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ordering in the menu joins Premium and Custom. Free and Pro read it as 0
 * through App\Enums\Feature::defaultValue(), so they need no key.
 */
return new class extends Migration
{
    private const INCLUDED = ['premium', 'custom'];

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

            if ($value === null) {
                unset($features['menu_ordering']);
            } else {
                $features['menu_ordering'] = $value;
            }

            DB::table('packages')->where('id', $package->id)->update(['features' => json_encode($features)]);
        }

        // Written past the model, so the cached entitlements are cleared here.
        Entitlements::flushAll();
    }
};
