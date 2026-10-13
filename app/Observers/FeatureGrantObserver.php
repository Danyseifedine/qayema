<?php

namespace App\Observers;

use App\Models\FeatureGrant;
use App\Services\Packages\Entitlements;

/**
 * A grant gives one restaurant more than its package: its entitlements are
 * worked out again, and a flag it brings arrives switched on, as with a new
 * package.
 */
class FeatureGrantObserver
{
    public function saving(FeatureGrant $grant): void
    {
        $grant->inReachBeforeSave = $grant->restaurant->featuresInReach();
    }

    public function saved(FeatureGrant $grant): void
    {
        Entitlements::flush($grant->restaurant_id);
        $grant->restaurant->switchOnWhatCameIntoReach($grant->inReachBeforeSave);
    }

    public function deleted(FeatureGrant $grant): void
    {
        Entitlements::flush($grant->restaurant_id);
    }
}
