<?php

namespace App\Services\Portal;

use App\Enums\Feature;
use App\Models\Package;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Number;

/**
 * The landing page's pricing cards, read from the packages an admin edits at
 * /admin → Packages, so the page can never promise what a package does not
 * hold. Each card lists what the package includes the way the dashboard's
 * Package page does: "Everything in Pro, plus" and only what grows or is new,
 * when it really has everything the package before it has.
 */
class PricingCards
{
    /** @var Collection<int, Package>|null */
    private ?Collection $packages = null;

    /**
     * @return list<array{
     *     name: string,
     *     price: string,
     *     per: string|null,
     *     description: string,
     *     base: string|null,
     *     lines: list<string>,
     *     featured: bool,
     *     free: bool,
     *     contact: bool,
     * }>
     */
    public function all(): array
    {
        $packages = $this->packages();
        $cards = [];

        foreach ($packages as $index => $package) {
            $previous = $packages[$index - 1] ?? null;
            $covers = $previous !== null && $this->covers($package, $previous);

            $features = array_values(array_filter(
                Feature::cases(),
                // Compared as owners see them: two "Unlimited"s are no change.
                fn (Feature $feature): bool => $feature->isLimit()
                    ? ! $covers || $package->shownValue($feature) !== $previous->shownValue($feature)
                    : $package->featureValue($feature) > 0 && (! $covers || $previous->featureValue($feature) === 0),
            ));

            $cards[] = [
                'name' => $package->name,
                'price' => $this->price($package),
                'per' => $package->price_cents > 0 ? __('portal.pricing.per_month') : null,
                'description' => (string) $package->description,
                'base' => $covers ? $previous->name : null,
                // The admin's own lines when written (Custom: a design of their
                // own...), what it adds from its features otherwise.
                'lines' => $package->highlightsIn(app()->getLocale())
                    ?: array_map(fn (Feature $feature): string => $this->line($package, $feature), $features),
                'featured' => $package->is_featured,
                'free' => $package->price_cents === 0,
                'contact' => $package->is_contact_only,
            ];
        }

        return $cards;
    }

    /**
     * The first package, in the order they are shown, that includes each
     * feature: the name a landing card puts beside it ("Premium"). A feature
     * every package includes has none.
     *
     * @return array<string, string> feature slug => package name
     */
    public function unlockedBy(): array
    {
        $packages = $this->packages();
        $names = [];

        foreach (Feature::flags() as $feature) {
            $first = $packages->first(fn (Package $package): bool => $package->featureValue($feature) > 0);

            if ($first !== null && $first->isNot($packages->first())) {
                $names[$feature->value] = $first->name;
            }
        }

        return $names;
    }

    /**
     * The fair-use numbers behind every "Unlimited*" on the cards, as the
     * footnote under them: "* Fair use on Premium: up to 1,000 dishes and
     * 1,000 categories." Null when no package has one.
     */
    public function fairUseNote(): ?string
    {
        $notes = [];

        foreach ($this->packages() as $package) {
            $limits = [];

            foreach (Feature::limits() as $feature) {
                if ($package->isFairUse($feature)) {
                    $value = (int) $package->featureValue($feature);
                    $limits[] = trans_choice('portal.pricing.limits.'.$feature->value, $value, ['count' => Number::format($value)]);
                }
            }

            if ($limits !== []) {
                $notes[] = __('portal.pricing.fair_use_note', [
                    'package' => $package->name,
                    'limits' => implode(__('portal.pricing.and'), $limits),
                ]);
            }
        }

        return $notes === [] ? null : '* '.implode(' ', $notes);
    }

    /** Whether a package has at least everything the one before it has. */
    private function covers(Package $package, Package $previous): bool
    {
        foreach (Feature::cases() as $feature) {
            $value = $package->featureValue($feature);
            $before = $previous->featureValue($feature);

            // Null is unlimited, so it beats any number.
            $atLeast = $feature->isLimit()
                ? $value === null || ($before !== null && $value >= $before)
                : $value > 0 || $before === 0;

            if (! $atLeast) {
                return false;
            }
        }

        return true;
    }

    private function price(Package $package): string
    {
        return match (true) {
            $package->price_cents === null => __('portal.pricing.lets_talk'),
            $package->price_cents === 0 => __('portal.pricing.free'),
            default => Number::currency(
                $package->price_cents / 100,
                in: $package->currency,
                // Written the same in both languages, and shown left to right:
                // an Arabic currency format flips the symbol ("US$ 12").
                locale: 'en',
                precision: $package->price_cents % 100 === 0 ? 0 : 2,
            ),
        };
    }

    private function line(Package $package, Feature $feature): string
    {
        if (! $feature->isLimit()) {
            return __('portal.pricing.rows.'.$feature->value);
        }

        $value = $package->shownValue($feature);

        // A fair-use limit reads "Unlimited dishes*", with the number in the
        // footnote under the cards (fairUseNote()).
        return $value === null
            ? __('portal.pricing.unlimited.'.$feature->value).($package->isFairUse($feature) ? '*' : '')
            : trans_choice('portal.pricing.limits.'.$feature->value, $value, ['count' => Number::format($value)]);
    }

    /**
     * The packages in the order they are shown, read once for everything
     * this instance is asked: a page that shows the cards, their footnote
     * and the feature chips shares one instance and one query.
     *
     * @return Collection<int, Package>
     */
    private function packages(): Collection
    {
        return $this->packages ??= Package::onOffer();
    }
}
