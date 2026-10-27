<?php

namespace App\Observers;

use App\Models\Package;
use App\Models\PreviousSlug;
use App\Models\Restaurant;
use App\Models\User;
use App\Services\Menu\MenuCache;
use App\Services\Packages\Entitlements;
use Illuminate\Support\Str;

/**
 * What saving or deleting a restaurant sets off, whichever path did it (the
 * dashboard, the admin, PackageAssigner, a console command): its first
 * package and link, the package history, a former link that keeps
 * forwarding, the menu Cloudflare keeps, and the owner's account going
 * with it.
 */
class RestaurantObserver
{
    public function creating(Restaurant $restaurant): void
    {
        // Every restaurant is on a package from the moment it exists, so
        // limits never have to cope with "no package yet".
        $restaurant->package_id ??= Package::default()?->id;
        $restaurant->package_started_at ??= now();

        if (empty($restaurant->slug)) {
            $base = Str::slug($restaurant->name);
            $slug = $base !== '' ? $base : 'menu';
            $count = 2;
            while (Restaurant::query()->where('slug', $slug)->exists()) {
                $slug = $base.'-'.$count;
                $count++;
            }
            $restaurant->slug = $slug;
        }
    }

    /**
     * Every write to the package fields lands in the history: the first one
     * here. Cloudflare forgets the new link, as the 404 a guest got there
     * before it existed may be kept.
     */
    public function created(Restaurant $restaurant): void
    {
        $restaurant->recordPackageChange(null);
        app(MenuCache::class)->forget($restaurant);
    }

    public function updating(Restaurant $restaurant): void
    {
        if ($restaurant->isDirty(Restaurant::PACKAGE_FIELDS)) {
            // The row as it still is in the database, before this save.
            $restaurant->inReachBeforeSave = $restaurant->newFromBuilder($restaurant->getRawOriginal())->featuresInReach();
        }
    }

    /**
     * A new menu link: the old one keeps forwarding (printed QR codes,
     * shared links), and taking back a former link frees it.
     */
    public function updated(Restaurant $restaurant): void
    {
        if (! $restaurant->wasChanged('slug')) {
            return;
        }

        $old = (string) $restaurant->getOriginal('slug');
        if ($old !== '') {
            PreviousSlug::query()->updateOrCreate(['slug' => $old], ['restaurant_id' => $restaurant->id]);
        }
        PreviousSlug::query()->where('slug', $restaurant->slug)->delete();
    }

    public function saved(Restaurant $restaurant): void
    {
        // What guests see changed: Cloudflare forgets the menu, under the
        // link it just left too (MenuCache). A save that changed nothing, or
        // only what the admin keeps for itself (the price, Whish), clears
        // nothing, as each purge spends Cloudflare's budget.
        if (array_diff(array_keys($restaurant->getChanges()), Restaurant::ADMIN_ONLY_FIELDS) !== []) {
            app(MenuCache::class)->forget($restaurant, (string) ($restaurant->wasChanged('slug') ? $restaurant->getOriginal('slug') : ''));
        }

        // Only an update carries package changes; the insert is recorded in
        // created(). A new price alone is history too, with no other effect.
        if (! $restaurant->wasChanged([...Restaurant::PACKAGE_FIELDS, 'price_cents'])) {
            return;
        }

        $restaurant->recordPackageChange($restaurant->getOriginal('package_id'));

        if (! $restaurant->wasChanged(Restaurant::PACKAGE_FIELDS)) {
            return;
        }

        Entitlements::flush($restaurant->id);
        // The loaded package may be the one it just left; what came into
        // reach is worked out from the new one, just below (read only when
        // the one in hand is not it: PackageAssigner hands it over).
        if (! $restaurant->relationLoaded('package') || $restaurant->package?->id !== $restaurant->package_id) {
            $restaurant->load('package');
        }
        $restaurant->switchOnWhatCameIntoReach($restaurant->inReachBeforeSave);
    }

    /**
     * Dishes are deleted one by one rather than by the database's cascade,
     * so each takes its photo out of storage; the restaurant's own logo and
     * cover go with it through the media library.
     */
    public function deleting(Restaurant $restaurant): void
    {
        // Through the models, so each photo leaves storage with its row.
        $restaurant->dishes()->get()->each->delete();
        $restaurant->offers()->get()->each->delete();
        // A design made for it alone goes too, with its thumbnail.
        $restaurant->ownDesigns()->get()->each->delete();
    }

    public function deleted(Restaurant $restaurant): void
    {
        // A restaurant and its owner's account go together: an owner without
        // a restaurant has nothing to sign in to. An admin's account is
        // never deleted this way.
        if (! $restaurant->ownerIsBeingDeleted) {
            $owner = User::query()->find($restaurant->user_id);

            if ($owner !== null && ! $owner->isAdmin()) {
                $owner->delete();
            }
        }

        // Gone, Cloudflare forgets the link it had.
        app(MenuCache::class)->forget($restaurant, (string) $restaurant->slug);
    }
}
