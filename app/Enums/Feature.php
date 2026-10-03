<?php

namespace App\Enums;

/**
 * Everything a restaurant's package can grant. This enum is the registry;
 * adding a limit or a flag means adding a case here and nothing else: the
 * package form renders from `cases()`, the dashboard's `plan` is built from
 * `flags()`, and App\Services\Packages\Entitlements resolves it against the
 * package and any grants.
 */
enum Feature: string
{
    case DishLimit = 'dish_limit';
    case CategoryLimit = 'category_limit';
    case SocialLinkLimit = 'social_link_limit';
    case MultipleLanguages = 'multiple_languages';
    case Variants = 'variants';
    case Addons = 'addons';
    case Appearance = 'appearance';
    case PremiumDesigns = 'premium_designs';
    case QrStudio = 'qr_studio';
    case Ordering = 'ordering';
    case MenuOrdering = 'menu_ordering';
    case Analytics = 'analytics';
    case AdvancedAnalytics = 'advanced_analytics';

    /**
     * Every case is listed, with no default arm: a new case without a kind
     * fails loudly instead of quietly becoming a limit.
     */
    public function kind(): FeatureKind
    {
        return match ($this) {
            self::DishLimit, self::CategoryLimit, self::SocialLinkLimit => FeatureKind::Limit,
            self::MultipleLanguages, self::Variants, self::Addons, self::Appearance, self::PremiumDesigns,
            self::QrStudio, self::Ordering, self::MenuOrdering, self::Analytics, self::AdvancedAnalytics => FeatureKind::Flag,
        };
    }

    public function isLimit(): bool
    {
        return $this->kind() === FeatureKind::Limit;
    }

    /** @return array<int, self> */
    public static function flags(): array
    {
        return array_values(array_filter(self::cases(), fn (self $feature): bool => ! $feature->isLimit()));
    }

    /** @return array<int, self> */
    public static function limits(): array
    {
        return array_values(array_filter(self::cases(), fn (self $feature): bool => $feature->isLimit()));
    }

    /**
     * The value a fresh install's default package starts with, and what a
     * package that does not carry the key yet reads as. After that the admin
     * panel owns these numbers.
     */
    public function defaultValue(): int
    {
        return match ($this) {
            self::DishLimit => 40,
            self::CategoryLimit => 8,
            self::SocialLinkLimit => 1,
            self::MultipleLanguages, self::Variants, self::Addons, self::Appearance, self::PremiumDesigns,
            self::QrStudio, self::Ordering, self::MenuOrdering, self::Analytics, self::AdvancedAnalytics => 0,
        };
    }

    public function label(): string
    {
        return __('features.'.$this->value);
    }

    /** One line on what the feature unlocks, for the admin's package form. */
    public function hint(): string
    {
        return __('features.hints.'.$this->value);
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
