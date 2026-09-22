<?php

namespace App\Enums;

/**
 * Everything a restaurant's package can grant. This enum is the registry —
 * adding a limit or a flag means adding a case here and nothing else: the
 * defaults table seeds itself from `cases()`, the admin page renders from it,
 * and App\Services\Global\Package resolves it.
 */
enum Feature: string
{
    case DishLimit = 'dish_limit';
    case CategoryLimit = 'category_limit';
    case SocialLinkLimit = 'social_link_limit';
    case QrStudio = 'qr_studio';

    public function kind(): FeatureKind
    {
        return match ($this) {
            self::QrStudio => FeatureKind::Flag,
            default => FeatureKind::Limit,
        };
    }

    public function isLimit(): bool
    {
        return $this->kind() === FeatureKind::Limit;
    }

    /**
     * The value a fresh install starts with, used to seed `feature_defaults`.
     * After that the admin panel owns these numbers.
     */
    public function defaultValue(): int
    {
        return match ($this) {
            self::DishLimit => 40,
            self::CategoryLimit => 10,
            self::SocialLinkLimit => 2,
            self::QrStudio => 0,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::DishLimit => __('features.dish_limit'),
            self::CategoryLimit => __('features.category_limit'),
            self::SocialLinkLimit => __('features.social_link_limit'),
            self::QrStudio => __('features.qr_studio'),
        };
    }

    /**
     * @return array<string, string> slug => label, for admin selects.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $feature): array => [$feature->value => $feature->label()])
            ->all();
    }
}
