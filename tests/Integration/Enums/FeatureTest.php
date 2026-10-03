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
            'dish_limit', 'category_limit', 'social_link_limit', 'multiple_languages', 'variants', 'addons',
            'appearance', 'premium_designs', 'qr_studio', 'ordering', 'menu_ordering', 'analytics', 'advanced_analytics',
        ], array_map(fn (Feature $feature): string => $feature->value, Feature::cases()));
    }

    public function test_the_three_limits_and_the_ten_flags(): void
    {
        $this->assertSame([Feature::DishLimit, Feature::CategoryLimit, Feature::SocialLinkLimit], Feature::limits());
        $this->assertSame([
            Feature::MultipleLanguages, Feature::Variants, Feature::Addons, Feature::Appearance, Feature::PremiumDesigns, Feature::QrStudio,
            Feature::Ordering, Feature::MenuOrdering, Feature::Analytics, Feature::AdvancedAnalytics,
        ], Feature::flags());
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

        $this->assertSame('استوديو QR', Feature::QrStudio->label());
        $this->assertSame('لغة ثانية في القائمة إلى جانب الإنجليزية.', Feature::MultipleLanguages->hint());
    }

    public function test_options_are_slug_to_label_in_case_order(): void
    {
        app()->setLocale('en');

        $this->assertSame([
            'dish_limit' => 'Dishes',
            'category_limit' => 'Categories',
            'social_link_limit' => 'Social links',
            'multiple_languages' => 'Second menu language',
            'variants' => 'Variants',
            'addons' => 'Add-ons',
            'appearance' => 'Appearance',
            'premium_designs' => 'Premium designs',
            'qr_studio' => 'QR Studio',
            'ordering' => 'Ordering',
            'menu_ordering' => 'Ordering in the menu',
            'analytics' => 'Analytics',
            'advanced_analytics' => 'Advanced analytics',
        ], Feature::options());
    }
}
