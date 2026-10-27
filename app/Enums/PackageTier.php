<?php

namespace App\Enums;

/**
 * The metal a package's crown wears in the dashboard's top bar and More
 * page: what an owner reads at a glance as how far up the packages go.
 */
enum PackageTier: string
{
    case Bronze = 'bronze';
    case Silver = 'silver';
    case Gold = 'gold';

    /** @return array<string, string> value => label, for the admin's select. */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $tier): array => [$tier->value => ucfirst($tier->value)])->all();
    }
}
