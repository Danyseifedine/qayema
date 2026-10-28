<?php

namespace App\Models\Concerns;

use App\Enums\Feature;
use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * The package a restaurant is on: which one applies and when (its start,
 * its end and the grace days after it), and how much room its limits leave.
 */
trait HasPackage
{
    /**
     * Restaurants whose assigned package is in force now.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopePackageActive(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('package_started_at')->orWhere('package_started_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('package_ends_at')->orWhere('package_ends_at', '>', now()));
    }

    /**
     * Restaurants paying now: switched on, their package is in force and
     * what they pay a month (their own price, else the package's) is more
     * than nothing. A menu switched off, or a package that ended, leaves
     * the monthly income.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopePaying(Builder $query): Builder
    {
        // Qualified: the income figure joins the packages, which have a
        // price and an "active" switch too.
        $price = $query->qualifyColumn('price_cents');

        return $query->where($query->qualifyColumn('is_active'), true)->packageActive()->where(fn (Builder $paid) => $paid
            ->where($price, '>', 0)
            ->orWhere(fn (Builder $own) => $own
                ->whereNull($price)
                ->whereHas('package', fn (Builder $package) => $package->where('price_cents', '>', 0))));
    }

    /**
     * What the paying restaurants pay a month, a row per currency (their
     * package's), the most first, added up in SQL; the part paid through
     * Whish apart. Empty when nobody pays.
     *
     * @return Collection<int, array{currency: string, cents: int, whish_cents: int}>
     */
    public static function monthlyIncome(): Collection
    {
        $pays = 'coalesce(restaurants.price_cents, packages.price_cents)';

        return Restaurant::query()
            ->paying()
            ->join('packages', 'packages.id', '=', 'restaurants.package_id')
            ->toBase()
            ->selectRaw('packages.currency as currency')
            ->selectRaw("sum({$pays}) as cents")
            ->selectRaw("sum(case when restaurants.pays_with_whish = 1 then {$pays} else 0 end) as whish_cents")
            ->groupBy('packages.currency')
            ->orderByDesc('cents')
            ->orderBy('packages.currency')
            ->get()
            ->map(fn (object $row): array => [
                'currency' => (string) $row->currency,
                'cents' => (int) $row->cents,
                'whish_cents' => (int) $row->whish_cents,
            ]);
    }

    /**
     * Restaurants whose assigned package has a start still to come.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopePackageScheduled(Builder $query): Builder
    {
        return $query->where('package_started_at', '>', now());
    }

    /**
     * Restaurants whose assigned package has ended (in its grace days or
     * past them).
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopePackageExpired(Builder $query): Builder
    {
        return $query->where('package_ends_at', '<=', now());
    }

    /**
     * Restaurants guests can open: switched on, and not past the grace days
     * of an ended package (menuOnline()).
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopeMenuOnline(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $q) => $q->whereNull('package_ends_at')->orWhere('package_ends_at', '>', now()->subDays(self::graceDays())));
    }

    /**
     * Restaurants whose package is in force and ends within the given days.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopePackageEndingWithin(Builder $query, int $days): Builder
    {
        return $query->packageActive()->whereBetween('package_ends_at', [now(), now()->addDays($days)]);
    }

    /**
     * Restaurants on a package right now (effectivePackage()): assigned and
     * started, ended included, or on the default while their own has not
     * started yet.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopeOnPackage(Builder $query, int $packageId): Builder
    {
        return $query->where(function (Builder $q) use ($packageId): void {
            $q->where(fn (Builder $assigned) => $assigned->where('package_id', $packageId)
                ->where(fn (Builder $started) => $started->whereNull('package_started_at')->orWhere('package_started_at', '<=', now())));

            if ($packageId === Package::default()?->id) {
                $q->orWhere(fn (Builder $waiting) => $waiting->packageScheduled())
                    ->orWhereNull('package_id');
            }
        });
    }

    /** Where the assigned package stands between its start and end dates. */
    public function packageStatus(): PackageStatus
    {
        return match (true) {
            $this->package_started_at !== null && $this->package_started_at->isFuture() => PackageStatus::Scheduled,
            $this->package_ends_at !== null && $this->package_ends_at->isPast() => PackageStatus::Expired,
            default => PackageStatus::Active,
        };
    }

    /** True once an admin-set expiry has passed. */
    public function packageExpired(): bool
    {
        return $this->packageStatus() === PackageStatus::Expired;
    }

    /**
     * Whole days left in the package, rounded up ("ends today" is 0); null
     * when it runs forever or is not in force (ended, or yet to start).
     */
    public function packageDaysLeft(): ?int
    {
        return $this->packageStatus() === PackageStatus::Active && $this->package_ends_at !== null
            ? (int) max(0, ceil(now()->diffInSeconds($this->package_ends_at) / 86400))
            : null;
    }

    /** Days an ended package keeps the menu online (config/package.php). */
    public static function graceDays(): int
    {
        return max(0, (int) config('package.grace_days', 3));
    }

    /** When the menu goes offline: the grace days past the package's end; null when it never ends. */
    public function offlineAt(): ?CarbonImmutable
    {
        return $this->package_ends_at === null
            ? null
            : CarbonImmutable::instance($this->package_ends_at)->addDays(self::graceDays());
    }

    /**
     * Whether guests can open the menu, order and see the QR card: switched
     * on by the admin, and not past the grace days of an ended package.
     * Extending or assigning a package brings it back that moment.
     */
    public function menuOnline(): bool
    {
        return (bool) $this->is_active && ! (bool) $this->offlineAt()?->isPast();
    }

    /**
     * The package the limits actually come from: the assigned one, ended or
     * not (an ended package takes the menu offline, it never downgrades), and
     * the default only while the assigned one has not started.
     */
    public function effectivePackage(): ?Package
    {
        if ($this->package_id !== null && $this->packageStatus() !== PackageStatus::Scheduled) {
            return $this->package;
        }

        return Package::default();
    }

    /** A row in the package history for the save just made (RestaurantObserver). */
    public function recordPackageChange(?int $fromPackageId): void
    {
        $this->packageChanges()->create([
            'from_package_id' => $fromPackageId,
            'to_package_id' => $this->package_id,
            'starts_at' => $this->package_started_at,
            'ends_at' => $this->package_ends_at,
            'price_cents' => $this->price_cents,
            'changed_by' => auth()->id(),
            'note' => $this->packageChangeNote,
        ]);

        $this->packageChangeNote = null;
    }

    /**
     * What this restaurant pays a month, in its package's currency: its own
     * price when an admin set one, else the package's; null when the
     * package's price is not published.
     */
    public function monthlyPriceCents(): ?int
    {
        return $this->price_cents ?? $this->effectivePackage()?->price_cents;
    }

    /** A limit in force (the package plus grants); null means unlimited. */
    public function limitOf(Feature $limit): ?int
    {
        return $this->entitlements()->limit($limit);
    }

    /** The menu already holds as many dishes, categories, links or built dishes as the limit allows; never when unlimited. */
    public function hasReached(Feature $limit): bool
    {
        $allowed = $this->limitOf($limit);

        return $allowed !== null && $this->rowsCountedBy($limit)->count() >= $allowed;
    }

    /**
     * Refuses one more dish, category or link once the limit is reached, as
     * a validation error on `$field`. Call it on the restaurant read under
     * its row lock, so two creates at once cannot both slip past the limit.
     * `:limit` in a message is the limit; the fair-use one (a limit the
     * package shows as unlimited) gets it with thousands separators.
     *
     * @throws ValidationException
     */
    public function ensureRoomFor(Feature $limit, string $field, string $planMessage, ?string $fairUseMessage = null): void
    {
        if (! $this->hasReached($limit)) {
            return;
        }

        $allowed = (int) $this->limitOf($limit);

        throw ValidationException::withMessages([
            $field => $fairUseMessage !== null && $this->entitlements()->isFairUse($limit)
                ? __($fairUseMessage, ['limit' => number_format($allowed)])
                : __($planMessage, ['limit' => $allowed]),
        ]);
    }

    /** @return HasMany<covariant \Illuminate\Database\Eloquent\Model, $this> */
    private function rowsCountedBy(Feature $limit): HasMany
    {
        return match ($limit) {
            Feature::DishLimit => $this->dishes(),
            Feature::CategoryLimit => $this->categories(),
            Feature::SocialLinkLimit => $this->socialLinks(),
            Feature::BuildLimit => $this->dishes()->whereHas('builds'),
            default => throw new InvalidArgumentException("[{$limit->value}] does not count the menu's rows."),
        };
    }
}
