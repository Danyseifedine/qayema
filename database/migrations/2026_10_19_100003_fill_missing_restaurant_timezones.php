<?php

use App\Models\Restaurant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Restaurants made before onboarding saved a timezone have none, so their
 * hours were read in UTC ("open now" hours off in Beirut). Each gets its
 * country's timezone; one without a country is left for its owner to set.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('restaurants')
            ->whereNull('timezone')
            ->whereNotNull('country_code')
            ->select(['id', 'country_code'])
            ->orderBy('id')
            ->chunkById(500, function ($restaurants): void {
                foreach ($restaurants as $restaurant) {
                    $timezone = Restaurant::likelyTimezone(null, $restaurant->country_code);

                    if ($timezone !== null) {
                        DB::table('restaurants')->where('id', $restaurant->id)->update(['timezone' => $timezone]);
                    }
                }
            });
    }

    /** Nothing to undo: a filled timezone is a correct one. */
    public function down(): void {}
};
