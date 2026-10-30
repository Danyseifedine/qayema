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

    /**
     * AI credits a month (App\Services\Ai\WritingHelpCredits). A package's
     * comes from its AI budget in dollars (Package::featureValue()); grants
     * give one restaurant its own number or extra.
     */
    case WritingHelpCredits = 'writing_help_credits';

    case MultipleLanguages = 'multiple_languages';
    case Variants = 'variants';
    case Addons = 'addons';
    case Appearance = 'appearance';
    case PremiumDesigns = 'premium_designs';
    case QrStudio = 'qr_studio';
    case Ordering = 'ordering';
    case MenuOrdering = 'menu_ordering';
    case DineIn = 'dine_in';
    case Analytics = 'analytics';
    case AdvancedAnalytics = 'advanced_analytics';
    case Offers = 'offers';

    /** Offers in each place switched on at once (OfferPlacement::limit()). */
    case OfferBannerLimit = 'offer_banner_limit';

    case OfferPopupLimit = 'offer_popup_limit';

    case OfferAdLimit = 'offer_ad_limit';

    /** Orders at the table sent on WhatsApp (DineIn is the in-menu way). */
    case DineInWhatsApp = 'dine_in_whatsapp';

    /** A guest follows an order placed in the menu, and may change it until accepted. */
    case OrderTracking = 'order_tracking';

    /** An offer may run on dates, weekdays, days of the month or hours. */
    case OfferSchedules = 'offer_schedules';

    /** An offer may take a discount off its dishes. */
    case OfferDiscounts = 'offer_discounts';

    /**
     * "Build your own" on a dish: half and half pizzas, three scoops
     * (App\Models\DishBuild). On no package: an admin switches it on for a
     * restaurant.
     */
    case BuildYourOwn = 'build_your_own';

    /**
     * How many dishes guests build (dishes with a build) a restaurant may
     * have, while BuildYourOwn is on. 3 unless an admin sets another.
     */
    case BuildLimit = 'build_limit';

    /**
     * Connect to POS: the menu's categories and dishes come from a POS
     * branch (App\Models\PosConnection). On no package: an admin switches it
     * on for a restaurant.
     */
    case PosConnection = 'pos_connection';

    /**
     * Every case is listed, with no default arm: a new case without a kind
     * fails loudly instead of quietly becoming a limit.
     */
    public function kind(): FeatureKind
    {
        return match ($this) {
            self::DishLimit, self::CategoryLimit, self::SocialLinkLimit, self::WritingHelpCredits,
            self::OfferBannerLimit, self::OfferPopupLimit, self::OfferAdLimit, self::BuildLimit => FeatureKind::Limit,
            self::MultipleLanguages, self::Variants, self::Addons, self::Appearance, self::PremiumDesigns,
            self::QrStudio, self::Ordering, self::MenuOrdering, self::DineIn, self::Analytics, self::AdvancedAnalytics,
            self::Offers, self::DineInWhatsApp, self::OrderTracking, self::OfferSchedules, self::OfferDiscounts,
            self::BuildYourOwn, self::PosConnection => FeatureKind::Flag,
        };
    }

    public function isLimit(): bool
    {
        return $this->kind() === FeatureKind::Limit;
    }

    /**
     * Listed on the landing page's pricing cards. The AI's credits
     * are a dashboard detail, kept off the public page (which claims no AI); the
     * parts of offers and ordering an admin may sell apart are told by their
     * Offers and ordering rows.
     */
    public function isOnPricing(): bool
    {
        return ! in_array($this, [
            self::WritingHelpCredits, self::OfferBannerLimit, self::OfferPopupLimit, self::OfferAdLimit,
            self::DineInWhatsApp, self::OrderTracking, self::OfferSchedules, self::OfferDiscounts, self::BuildYourOwn,
            self::BuildLimit, self::PosConnection,
        ], true);
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
            // No package at all has no AI budget, so no credits.
            self::WritingHelpCredits => 0,
            // Used only where offers are on (the `offers` flag).
            self::OfferBannerLimit => 3,
            self::OfferPopupLimit, self::OfferAdLimit => 1,
            // Used only where an admin switched Build your own on.
            self::BuildLimit => 3,
            self::MultipleLanguages, self::Variants, self::Addons, self::Appearance, self::PremiumDesigns,
            self::QrStudio, self::Ordering, self::MenuOrdering, self::DineIn, self::Analytics, self::AdvancedAnalytics,
            self::Offers, self::DineInWhatsApp, self::OrderTracking, self::OfferSchedules, self::OfferDiscounts,
            self::BuildYourOwn, self::PosConnection => 0,
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
