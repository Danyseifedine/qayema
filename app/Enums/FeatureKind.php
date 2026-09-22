<?php

namespace App\Enums;

/**
 * How a feature's grants combine: limits add up into an allowance, flags are
 * simply on or off.
 */
enum FeatureKind: string
{
    case Limit = 'limit';
    case Flag = 'flag';
}
