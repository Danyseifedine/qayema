<?php

namespace App\Services\Packages;

use App\Models\Package;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * The one way an admin changes what package a restaurant is on and for how
 * long: the table's Change package / Extend / Back to default actions, the
 * bulk action and "Apply this package" on a request all call it. Each call is
 * one save, which the restaurant's save hook turns into a history row
 * (Restaurant::packageChanges()) carrying the note given here.
 */
class PackageAssigner
{
    /** The durations the admin picks from, in months. */
    public const DURATIONS = [1, 3, 6, 12];

    /**
     * Put a restaurant on a package from `$startsAt` (now when null) until
     * `$endsAt` (forever when null).
     */
    public function assign(
        Restaurant $restaurant,
        Package $package,
        ?CarbonInterface $startsAt = null,
        ?CarbonInterface $endsAt = null,
        ?string $note = null,
    ): Restaurant {
        $startsAt ??= now();

        $restaurant->packageChangeNote = $note;
        $restaurant->forceFill([
            'package_id' => $package->id,
            'package_started_at' => $startsAt,
            'package_ends_at' => $endsAt,
        ])->save();

        return $restaurant;
    }

    /**
     * Give the current package more time: `$months` more from its end, or
     * from now when it already ended; null makes it forever.
     */
    public function extend(Restaurant $restaurant, ?int $months, ?string $note = null): Restaurant
    {
        // A package that runs forever has nothing to extend; months would
        // give it an end and cut it short.
        if ($restaurant->package_ends_at === null) {
            return $restaurant;
        }

        $restaurant->packageChangeNote = $note;

        if ($restaurant->packageExpired()) {
            // An ended package is back in force from today.
            $restaurant->package_started_at = now();
        }

        $restaurant->package_ends_at = $months === null ? null : self::endAfter($restaurant->package_ends_at, $months);
        $restaurant->save();

        return $restaurant;
    }

    /** Back on the default package, forever. */
    public function reset(Restaurant $restaurant, ?string $note = null): Restaurant
    {
        return $this->assign($restaurant, Package::default() ?? $restaurant->package, now(), null, $note);
    }

    /** The end of a run of `$months`, starting from the given end or from now if it passed. */
    public static function endAfter(?CarbonInterface $currentEnd, int $months): CarbonImmutable
    {
        $from = $currentEnd !== null && $currentEnd->isFuture() ? CarbonImmutable::instance($currentEnd) : CarbonImmutable::now();

        return $from->addMonthsNoOverflow($months);
    }
}
