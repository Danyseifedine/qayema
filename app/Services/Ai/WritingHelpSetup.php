<?php

namespace App\Services\Ai;

use App\Enums\Feature;
use App\Models\Restaurant;

/**
 * Whether the writing help runs, and on what: read from the config, so
 * switching it on, or to another provider or model, is an .env edit. On only
 * while config('services.writing_help.provider') names a provider whose key
 * is set (a server with no key simply has no AI buttons), and for a
 * restaurant whose package gives it uses (Feature::WritingHelpLimit, 0 for
 * none). The calls themselves are WritingHelp's.
 */
class WritingHelpSetup
{
    public static function isOn(): bool
    {
        $provider = self::provider();

        return $provider !== null && filled(config("ai.providers.{$provider}.key"));
    }

    /** On for this restaurant: on at all, and not 0 uses a day on its package. */
    public static function isOnFor(Restaurant $restaurant): bool
    {
        return self::isOn() && $restaurant->entitlements()->limit(Feature::WritingHelpLimit) !== 0;
    }

    public static function provider(): ?string
    {
        return config('services.writing_help.provider') ?: null;
    }

    /** Null leaves the agents on their provider's cheapest model. */
    public static function model(): ?string
    {
        return config('services.writing_help.model') ?: null;
    }
}
