<?php

namespace App\Services\Packages;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Models\FeatureGrant;
use App\Models\Restaurant;

/**
 * One restaurant's features as the admin edits them on its page, like a
 * package: every switch and number as it stands now (its package with any
 * grants), and what its package alone gives. Saving changes only what was
 * changed, as a lasting "set" grant (GrantMode::Set, no end), which wins over
 * the package and any other grant; set back to the package's value, the
 * lasting grant goes and the package decides again. Timed extras and ones
 * added on top stay on the restaurant's "Extras & overrides" tab.
 */
final class RestaurantFeatures
{
    /** What a lasting change made here is noted with. */
    public const NOTE = 'Set on the restaurant page';

    /**
     * Every feature as it stands now: a number (null unlimited) or 0/1.
     *
     * @return array<string, int|null>
     */
    public static function state(Restaurant $restaurant): array
    {
        $all = Entitlements::for($restaurant)->all();
        $state = [];

        foreach (Feature::cases() as $feature) {
            $state[$feature->value] = self::reading($feature, array_key_exists($feature->value, $all) ? $all[$feature->value] : $feature->defaultValue());
        }

        return $state;
    }

    /** What the restaurant's package alone gives for a feature (null unlimited). */
    public static function fromPackage(Restaurant $restaurant, Feature $feature): ?int
    {
        $package = $restaurant->effectivePackage();

        return $package === null ? $feature->defaultValue() : $package->featureValue($feature);
    }

    /**
     * Of what the form sent, only what differs from what holds now: read
     * before the page saves, so a package changed in the same save does not
     * look like every feature changed (and get pinned to the old package).
     *
     * @param  array<string, mixed>  $submitted
     * @return array<string, int|null>
     */
    public static function changed(Restaurant $restaurant, array $submitted): array
    {
        $now = self::state($restaurant);
        $changed = [];

        foreach (Feature::cases() as $feature) {
            if (array_key_exists($feature->value, $submitted)) {
                $wanted = self::normalize($feature, $submitted[$feature->value]);
                if ($wanted !== $now[$feature->value]) {
                    $changed[$feature->value] = $wanted;
                }
            }
        }

        return $changed;
    }

    /**
     * Writes what the admin changed (changed()). A feature left as it stands is not
     * touched, so a timed extra or an older grant on it is kept.
     *
     * @param  array<string, mixed>  $submitted  feature slug => value (null unlimited, a flag true/false or 0/1)
     */
    public static function save(Restaurant $restaurant, array $submitted): void
    {
        $now = self::state($restaurant);
        // Read once: each feature's value without its old lasting change is
        // worked out from these, not read again per feature.
        $package = $restaurant->effectivePackage();
        $grants = $restaurant->featureGrants()->active()->get();

        foreach (Feature::cases() as $feature) {
            if (! array_key_exists($feature->value, $submitted)) {
                continue;
            }

            $wanted = self::normalize($feature, $submitted[$feature->value]);

            if ($wanted === self::reading($feature, $now[$feature->value] ?? null)) {
                continue;
            }

            // The lasting changes made here before give way to this one.
            $lasting = $grants->filter(fn (FeatureGrant $grant): bool => $grant->feature === $feature
                && $grant->mode === GrantMode::Set
                && $grant->ends_at === null);
            $lasting->each->delete();
            $grants = $grants->diffKeys($lasting);

            $without = Entitlements::valuesFrom($package, $grants)[$feature->value];

            if ($wanted !== self::reading($feature, $without)) {
                $grant = $restaurant->featureGrants()->make([
                    'feature' => $feature,
                    'mode' => GrantMode::Set,
                    'value' => $wanted,
                    'source' => 'admin',
                    'note' => self::NOTE,
                ]);
                // Its observer reads the restaurant: hand it over rather than read it again.
                $grant->setRelation('restaurant', $restaurant)->save();
                $grants->push($grant);
            }
        }
    }

    /** A resolved value as the form shows it: a flag 0 or 1 (null is on), a limit as it is. */
    public static function reading(Feature $feature, ?int $value): ?int
    {
        return $feature->isLimit() ? $value : (int) ($value === null || $value > 0);
    }

    /** A form's value as the grant holds it: a flag 0 or 1, a limit a number or null for unlimited. */
    private static function normalize(Feature $feature, mixed $value): ?int
    {
        if (! $feature->isLimit()) {
            return (int) (bool) $value;
        }

        return $value === null || $value === '' ? null : max(0, (int) $value);
    }
}
