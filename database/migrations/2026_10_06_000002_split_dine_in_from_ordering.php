<?php

use App\Services\Packages\Entitlements;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Ordering at the table becomes a feature of its own (`dine_in`), beside
 * ordering: its own package flag, its own switch on the Features page, and
 * its orders always placed in the menu, whichever way delivery and pickup
 * come in.
 *
 * Premium and Custom get the flag; Free and Pro read it as 0 through
 * App\Enums\Feature::defaultValue(). "dine_in" leaves `order_types`, which
 * is delivery and pickup again.
 */
return new class extends Migration
{
    private const INCLUDED = ['premium', 'custom'];

    public function up(): void
    {
        $this->setFlag(1);

        foreach (DB::table('restaurants')->whereNotNull('order_types')->get(['id', 'order_types']) as $restaurant) {
            $types = json_decode((string) $restaurant->order_types, true) ?: [];
            $away = array_values(array_diff($types, ['dine_in']));

            if ($away !== $types) {
                DB::table('restaurants')->where('id', $restaurant->id)->update([
                    'order_types' => $away === [] ? null : json_encode($away),
                ]);
            }
        }
    }

    public function down(): void
    {
        $this->setFlag(null);
    }

    private function setFlag(?int $value): void
    {
        foreach (DB::table('packages')->whereIn('slug', self::INCLUDED)->get() as $package) {
            $features = json_decode((string) $package->features, true) ?: [];

            if ($value === null) {
                unset($features['dine_in']);
            } else {
                $features['dine_in'] = $value;
            }

            DB::table('packages')->where('id', $package->id)->update(['features' => json_encode($features)]);
        }

        // Written past the model, so the cached entitlements are cleared here.
        Entitlements::flushAll();
    }
};
