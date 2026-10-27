<?php

namespace App\Services\Ai;

use App\Enums\Feature;
use App\Models\Restaurant;
use Laravel\Ai\AiManager;

/**
 * Whether the writing help runs, and on what: read from the config, so
 * switching it on, or to another provider or model, is an .env edit. On only
 * while config('services.writing_help.provider') names a provider whose key
 * is set (a server with no key simply has no AI buttons), and for a
 * restaurant whose package gives it credits a month (Feature::WritingHelpCredits,
 * 0 for none). The calls themselves are WritingHelp's.
 */
class WritingHelpSetup
{
    public static function isOn(): bool
    {
        $provider = self::provider();

        return $provider !== null && filled(config("ai.providers.{$provider}.key"));
    }

    /** On for this restaurant: on at all, and not 0 credits a month. */
    public static function isOnFor(Restaurant $restaurant): bool
    {
        return self::isOn() && $restaurant->entitlements()->limit(Feature::WritingHelpCredits) !== 0;
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

    /**
     * The model the calls actually run on: the one set, or else the
     * provider's cheapest (what #[UseCheapestModel] picks), so the thinking
     * switch and the price know it is Haiku even with AI_MODEL empty. Null
     * with no provider.
     */
    public static function effectiveModel(): ?string
    {
        $provider = self::provider();

        if ($provider === null) {
            return null;
        }

        return self::model() ?? app(AiManager::class)->textProvider($provider)->cheapestTextModel();
    }
}
