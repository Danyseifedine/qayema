<?php

namespace App\Enums;

/**
 * How a grant meets the package: `Add` stacks on top of it (more dishes, an
 * add-on unlocked), `Set` decides the feature for this restaurant whatever
 * the package and other grants say (a lower or higher limit, unlimited, or
 * an on/off feature turned off or on).
 */
enum GrantMode: string
{
    case Add = 'add';
    case Set = 'set';

    public function label(): string
    {
        return match ($this) {
            self::Add => 'Add on top of the package',
            self::Set => 'Set exactly (overrides the package)',
        };
    }
}
