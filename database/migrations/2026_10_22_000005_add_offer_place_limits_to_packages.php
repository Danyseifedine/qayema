<?php

use App\Services\Packages\Entitlements;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * How many banners, pop-ups and short ads an owner may have switched on at
 * once, written on the packages that carry offers. The others read
 * App\Enums\Feature::defaultValue() and only use it if offers are granted.
 */
return new class extends Migration
{
    private const INCLUDED = ['premium', 'custom'];

    private const LIMITS = ['offer_banner_limit' => 3, 'offer_popup_limit' => 1, 'offer_ad_limit' => 1];

    public function up(): void
    {
        $this->each(function (array $features): array {
            return $features + self::LIMITS;
        });
    }

    public function down(): void
    {
        $this->each(fn (array $features): array => array_diff_key($features, self::LIMITS));
    }

    /** @param  callable(array<string, mixed>): array<string, mixed>  $change */
    private function each(callable $change): void
    {
        foreach (DB::table('packages')->whereIn('slug', self::INCLUDED)->get() as $package) {
            $features = json_decode((string) $package->features, true) ?: [];

            DB::table('packages')->where('id', $package->id)->update(['features' => json_encode($change($features))]);
        }

        // Written past the model, so the cached entitlements are cleared here.
        Entitlements::flushAll();
    }
};
