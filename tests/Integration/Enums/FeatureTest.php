<?php

namespace Tests\Integration\Enums;

use App\Enums\Feature;
use App\Enums\FeatureKind;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class FeatureTest extends TestCase
{
    public function test_every_case_declares_a_kind_a_label_and_a_default(): void
    {
        foreach (Feature::cases() as $feature) {
            $this->assertInstanceOf(FeatureKind::class, $feature->kind());
            $this->assertNotSame('', $feature->label(), "{$feature->value} has no label");
            $this->assertGreaterThanOrEqual(0, $feature->defaultValue());
        }
    }

    public function test_limits_and_flags_are_classified(): void
    {
        $this->assertTrue(Feature::DishLimit->isLimit());
        $this->assertTrue(Feature::CategoryLimit->isLimit());
        $this->assertTrue(Feature::SocialLinkLimit->isLimit());
        $this->assertTrue(Feature::WritingHelpLimit->isLimit());
        $this->assertFalse(Feature::QrStudio->isLimit());
        $this->assertSame(FeatureKind::Flag, Feature::QrStudio->kind());
    }

    public function test_a_flag_defaults_to_off(): void
    {
        $this->assertSame(0, Feature::QrStudio->defaultValue());
    }

    public function test_options_cover_every_case_keyed_by_slug(): void
    {
        $options = Feature::options();

        $this->assertCount(count(Feature::cases()), $options);
        foreach (Feature::cases() as $feature) {
            $this->assertArrayHasKey($feature->value, $options);
        }
    }

    public function test_labels_are_translated_per_locale(): void
    {
        app()->setLocale('en');
        $en = Feature::DishLimit->label();
        app()->setLocale('ar');
        $ar = Feature::DishLimit->label();

        $this->assertNotSame($en, $ar);
        $this->assertNotSame('features.dish_limit', $ar, 'Arabic key is present, not echoed back.');
    }

    public function test_the_registry_is_exactly_these_slugs(): void
    {
        $this->assertSame([
            'dish_limit', 'category_limit', 'social_link_limit', 'writing_help_limit', 'writing_help_credits', 'multiple_languages', 'variants', 'addons',
            'appearance', 'premium_designs', 'qr_studio', 'ordering', 'menu_ordering', 'dine_in', 'analytics', 'advanced_analytics', 'offers',
            'offer_banner_limit', 'offer_popup_limit', 'offer_ad_limit',
            'dine_in_whatsapp', 'order_tracking', 'offer_schedules', 'offer_discounts', 'build_your_own', 'build_limit',
        ], array_map(fn (Feature $feature): string => $feature->value, Feature::cases()));
    }

    public function test_the_nine_limits_and_the_seventeen_flags(): void
    {
        $this->assertSame([
            Feature::DishLimit, Feature::CategoryLimit, Feature::SocialLinkLimit, Feature::WritingHelpLimit, Feature::WritingHelpCredits,
            Feature::OfferBannerLimit, Feature::OfferPopupLimit, Feature::OfferAdLimit, Feature::BuildLimit,
        ], Feature::limits());
        $this->assertSame([
            Feature::MultipleLanguages, Feature::Variants, Feature::Addons, Feature::Appearance, Feature::PremiumDesigns, Feature::QrStudio,
            Feature::Ordering, Feature::MenuOrdering, Feature::DineIn, Feature::Analytics, Feature::AdvancedAnalytics, Feature::Offers,
            Feature::DineInWhatsApp, Feature::OrderTracking, Feature::OfferSchedules, Feature::OfferDiscounts, Feature::BuildYourOwn,
        ], Feature::flags());
    }

    public function test_the_ais_uses_and_credits_and_the_offer_places_stay_off_the_pricing_cards(): void
    {
        $this->assertSame(
            [
                Feature::WritingHelpLimit, Feature::WritingHelpCredits, Feature::OfferBannerLimit, Feature::OfferPopupLimit, Feature::OfferAdLimit,
                Feature::DineInWhatsApp, Feature::OrderTracking, Feature::OfferSchedules, Feature::OfferDiscounts, Feature::BuildYourOwn,
                Feature::BuildLimit,
            ],
            array_values(array_filter(Feature::cases(), fn (Feature $feature): bool => ! $feature->isOnPricing())),
        );
    }

    public function test_limits_and_flags_split_every_case_between_them(): void
    {
        $this->assertCount(count(Feature::cases()), [...Feature::limits(), ...Feature::flags()]);

        foreach (Feature::limits() as $feature) {
            $this->assertSame(FeatureKind::Limit, $feature->kind());
            $this->assertTrue($feature->isLimit());
        }

        foreach (Feature::flags() as $feature) {
            $this->assertSame(FeatureKind::Flag, $feature->kind());
            $this->assertFalse($feature->isLimit());
        }
    }

    public function test_the_default_values_are_the_free_package(): void
    {
        $this->assertSame(40, Feature::DishLimit->defaultValue());
        $this->assertSame(8, Feature::CategoryLimit->defaultValue());
        $this->assertSame(1, Feature::SocialLinkLimit->defaultValue());
        // The AI's uses a day: on for every package until an admin sets them.
        $this->assertSame(30, Feature::WritingHelpLimit->defaultValue());
        // A package's credits come from its AI budget; no package has none.
        $this->assertSame(0, Feature::WritingHelpCredits->defaultValue());
        // Offers in each place switched on at once, where offers are on.
        $this->assertSame(3, Feature::OfferBannerLimit->defaultValue());
        $this->assertSame(1, Feature::OfferPopupLimit->defaultValue());
        $this->assertSame(1, Feature::OfferAdLimit->defaultValue());
        // Dishes guests build, where an admin switched it on.
        $this->assertSame(3, Feature::BuildLimit->defaultValue());

        foreach (Feature::flags() as $feature) {
            $this->assertSame(0, $feature->defaultValue(), "{$feature->value} is on by default.");
        }
    }

    public function test_every_label_and_hint_is_written_in_english_and_arabic(): void
    {
        foreach (['en', 'ar'] as $locale) {
            foreach (Feature::cases() as $feature) {
                $this->assertTrue(Lang::has('features.'.$feature->value, $locale, false), "No {$locale} label for {$feature->value}.");
                $this->assertTrue(Lang::has('features.hints.'.$feature->value, $locale, false), "No {$locale} hint for {$feature->value}.");
            }
        }
    }

    public function test_labels_and_hints_read_from_the_lang_files(): void
    {
        app()->setLocale('en');

        $this->assertSame('Second menu language', Feature::MultipleLanguages->label());
        $this->assertSame('How many dishes the menu can hold.', Feature::DishLimit->hint());

        app()->setLocale('ar');

        $this->assertSame('تصميم رمز QR', Feature::QrStudio->label());
        $this->assertSame('لغة ثانية في القائمة إلى جانب الإنجليزية.', Feature::MultipleLanguages->hint());
    }

    public function test_options_are_slug_to_label_in_case_order(): void
    {
        app()->setLocale('en');

        $this->assertSame([
            'dish_limit' => 'Dishes',
            'category_limit' => 'Categories',
            'social_link_limit' => 'Social links',
            'writing_help_limit' => 'AI writing help a day',
            'writing_help_credits' => 'AI credits a month',
            'multiple_languages' => 'Second menu language',
            'variants' => 'Variants',
            'addons' => 'Add-ons',
            'appearance' => 'Appearance',
            'premium_designs' => 'Premium designs',
            'qr_studio' => 'QR Studio',
            'ordering' => 'Orders on WhatsApp',
            'menu_ordering' => 'Orders in the menu',
            'dine_in' => 'Table orders in the menu',
            'analytics' => 'Analytics',
            'advanced_analytics' => 'Advanced analytics',
            'offers' => 'Offers',
            'offer_banner_limit' => 'Offer banners',
            'offer_popup_limit' => 'Offer pop-ups',
            'offer_ad_limit' => 'Offer short ads',
            'dine_in_whatsapp' => 'Table orders on WhatsApp',
            'order_tracking' => 'Order tracking',
            'offer_schedules' => 'Offer schedules',
            'offer_discounts' => 'Offer discounts',
            'build_your_own' => 'Build your own',
            'build_limit' => 'Dishes guests build',
        ], Feature::options());
    }
}
