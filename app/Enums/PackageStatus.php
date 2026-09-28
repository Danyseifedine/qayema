<?php

namespace App\Enums;

/**
 * Where a restaurant's assigned package stands in its dates. Only an Active
 * package gives anything; before and after, the default package applies.
 */
enum PackageStatus: string
{
    /** In force: started, and not ended (or never ending). */
    case Active = 'active';

    /** Assigned with a start date still to come. */
    case Scheduled = 'scheduled';

    /** Its end date has passed. */
    case Expired = 'expired';
}
