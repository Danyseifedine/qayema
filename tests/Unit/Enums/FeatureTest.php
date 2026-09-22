<?php

namespace Tests\Unit\Enums;

use App\Enums\Feature;
use App\Enums\FeatureKind;
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
}
