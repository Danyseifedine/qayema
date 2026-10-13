<?php

namespace App\Enums;

/**
 * Where a restaurant's assigned package stands in its dates. Before it
 * starts the default package applies; once it ended the restaurant keeps it,
 * and its menu goes offline after the grace days (Restaurant::menuOnline()).
 */
enum PackageStatus: string
{
    /** In force: started, and not ended (or never ending). */
    case Active = 'active';

    /** Assigned with a start date still to come. */
    case Scheduled = 'scheduled';

    /** Its end date has passed (the menu stays online for the grace days). */
    case Expired = 'expired';
}
