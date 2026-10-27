<?php

use App\Services\Packages\Entitlements;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * What came bundled becomes switches of its own, so an admin can build a
 * package with only some of it: table orders on WhatsApp (apart from in the
 * menu), order tracking, offer schedules and offer discounts. Every package
 * that had what they came with keeps them on; the rest read 0.
 */
return new class extends Migration
{
    /** Each new switch, and the switch it came bundled with. */
    private const CAME_WITH = [
        'dine_in_whatsapp' => 'dine_in',
        'order_tracking' => 'menu_ordering',
        'offer_schedules' => 'offers',
        'offer_discounts' => 'offers',
    ];

    public function up(): void
    {
        $this->each(function (array $features): array {
            foreach (self::CAME_WITH as $switch => $bundle) {
                if (! array_key_exists($switch, $features) && (int) ($features[$bundle] ?? 0) === 1) {
                    $features[$switch] = 1;
                }
            }

            return $features;
        });

        // A restaurant given the bundle by the admin keeps all it brought.
        foreach (self::CAME_WITH as $switch => $bundle) {
            foreach (DB::table('feature_grants')->where('feature', $bundle)->get() as $grant) {
                $exists = DB::table('feature_grants')->where('restaurant_id', $grant->restaurant_id)->where('feature', $switch)->exists();
                if (! $exists) {
                    $copy = (array) $grant;
                    unset($copy['id']);
                    $copy['feature'] = $switch;
                    DB::table('feature_grants')->insert($copy);
                }
            }
        }

        Entitlements::flushAll();
    }

    public function down(): void
    {
        $this->each(fn (array $features): array => array_diff_key($features, self::CAME_WITH));
        DB::table('feature_grants')->whereIn('feature', array_keys(self::CAME_WITH))->delete();
        Entitlements::flushAll();
    }

    /** @param  callable(array<string, mixed>): array<string, mixed>  $change */
    private function each(callable $change): void
    {
        foreach (DB::table('packages')->get() as $package) {
            $features = json_decode((string) $package->features, true) ?: [];

            DB::table('packages')->where('id', $package->id)->update(['features' => json_encode($change($features))]);
        }
    }
};
