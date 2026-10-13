<?php

namespace App\Observers;

use App\Models\Package;
use App\Services\Menu\MenuCache;
use App\Services\Packages\Entitlements;
use App\Services\Packages\PackageAssigner;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;

/**
 * What saving or deleting a package sets off. A package's numbers sit
 * underneath every restaurant on it, and the default sits underneath every
 * restaurant without one, so any write invalidates all of them rather than
 * guess which.
 */
class PackageObserver
{
    /**
     * Every restaurant falls back on the default, so it is always on offer;
     * a package taken off offer is no one's "Most popular".
     */
    public function saving(Package $package): void
    {
        if ($package->is_default) {
            $package->is_active = true;
        }

        if (! $package->is_active) {
            $package->is_featured = false;
        }
    }

    public function saved(Package $package): void
    {
        $this->forgetWhatItGives();

        // "Most popular" is one package: marking one takes it off the others.
        if ($package->is_featured && ($package->wasRecentlyCreated || $package->wasChanged('is_featured'))) {
            Package::query()->whereKeyNot($package->getKey())->where('is_featured', true)->update(['is_featured' => false]);
        }
    }

    /**
     * Every restaurant falls back on the default package, so it is never
     * deleted. Restaurants on any other one move to the default first,
     * through the assigner, so their history says why.
     */
    public function deleting(Package $package): bool
    {
        if ($package->is_default) {
            return false;
        }

        $default = Package::default();
        $assigner = app(PackageAssigner::class);

        foreach ($default === null ? [] : $package->restaurants()->get() as $restaurant) {
            $assigner->assign($restaurant, $default, note: "The {$package->name} package was deleted.");
        }

        return true;
    }

    public function deleted(Package $package): void
    {
        $this->forgetWhatItGives();
    }

    private function forgetWhatItGives(): void
    {
        Cache::forget(Package::DEFAULT_CACHE_KEY);
        // default() keeps its answer for the request (once()).
        Once::flush();
        Entitlements::flushAll();
        // What a package gives shows on its menus (ordering, designs).
        app(MenuCache::class)->forgetAll();
    }
}
