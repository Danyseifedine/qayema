<?php

namespace Tests\Integration\Models;

use App\Enums\Feature;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What each package ships with on a fresh install, as decided with the
 * owner. After install the admin owns these numbers; this pins the seed.
 */
class PackageCatalogTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, int|null> */
    private function shipped(string $slug): array
    {
        $package = Package::findBySlug($slug);

        return collect(Feature::cases())
            ->mapWithKeys(fn (Feature $feature): array => [$feature->value => $package->featureValue($feature)])
            ->all();
    }

    public function test_free_is_a_plain_english_menu(): void
    {
        $this->assertSame([
            'dish_limit' => 40, 'category_limit' => 8, 'social_link_limit' => 1, 'writing_help_credits' => 500,
            'multiple_languages' => 0, 'variants' => 0, 'addons' => 0, 'appearance' => 0, 'premium_designs' => 0,
            'qr_studio' => 0, 'ordering' => 0, 'menu_ordering' => 0, 'dine_in' => 0, 'analytics' => 0, 'advanced_analytics' => 0,
            'offers' => 0,
            // Read from the defaults, used only if offers are granted.
            'offer_banner_limit' => 3, 'offer_popup_limit' => 1, 'offer_ad_limit' => 1,
            'dine_in_whatsapp' => 0, 'order_tracking' => 0, 'offer_schedules' => 0, 'offer_discounts' => 0, 'build_your_own' => 0, 'build_limit' => 3,
        ], $this->shipped('free'));
        $this->assertTrue(Package::findBySlug('free')->is_default);
    }

    public function test_pro_adds_its_own_look_two_languages_dish_choices_and_the_numbers(): void
    {
        $this->assertSame([
            'dish_limit' => 150, 'category_limit' => 15, 'social_link_limit' => 2, 'writing_help_credits' => 7500,
            'multiple_languages' => 1, 'variants' => 1, 'addons' => 1, 'appearance' => 1, 'premium_designs' => 0,
            'qr_studio' => 0, 'ordering' => 0, 'menu_ordering' => 0, 'dine_in' => 0, 'analytics' => 1, 'advanced_analytics' => 0,
            'offers' => 0,
            'offer_banner_limit' => 3, 'offer_popup_limit' => 1, 'offer_ad_limit' => 1,
            'dine_in_whatsapp' => 0, 'order_tracking' => 0, 'offer_schedules' => 0, 'offer_discounts' => 0, 'build_your_own' => 0, 'build_limit' => 3,
        ], $this->shipped('pro'));
        $this->assertFalse(Package::findBySlug('pro')->is_featured);
    }

    public function test_premium_has_everything(): void
    {
        $this->assertSame([
            'dish_limit' => 1000, 'category_limit' => 1000, 'social_link_limit' => 10, 'writing_help_credits' => 15000,
            'multiple_languages' => 1, 'variants' => 1, 'addons' => 1, 'appearance' => 1, 'premium_designs' => 1,
            'qr_studio' => 1, 'ordering' => 1, 'menu_ordering' => 1, 'dine_in' => 1, 'analytics' => 1, 'advanced_analytics' => 1,
            'offers' => 1,
            // Three banners, one pop-up and one short ad switched on at once.
            'offer_banner_limit' => 3, 'offer_popup_limit' => 1, 'offer_ad_limit' => 1,
            // What offers and table ordering brought, now switches of their own.
            'dine_in_whatsapp' => 1, 'order_tracking' => 1, 'offer_schedules' => 1, 'offer_discounts' => 1,
            // On no package: an admin switches it on for a restaurant.
            'build_your_own' => 0, 'build_limit' => 3,
        ], $this->shipped('premium'));
        // Its dishes and categories read "Unlimited", with 1,000 as fair use.
        $this->assertSame(['dish_limit', 'category_limit'], Package::findBySlug('premium')->fair_use);
        // Premium is the one marked "Most popular", and the only one.
        $this->assertSame(['premium'], Package::query()->where('is_featured', true)->pluck('slug')->all());
    }

    public function test_custom_is_unlimited_with_everything_and_asked_for(): void
    {
        $shipped = $this->shipped('custom');

        $this->assertNull($shipped['dish_limit']);
        $this->assertNull($shipped['category_limit']);
        $this->assertNull($shipped['social_link_limit']);
        // Never unlimited: every use is a paid call.
        $this->assertSame(15000, $shipped['writing_help_credits']);
        foreach (Feature::flags() as $flag) {
            // On no package: an admin switches it on for a restaurant.
            $this->assertSame($flag === Feature::BuildYourOwn ? 0 : 1, $shipped[$flag->value], "custom lacks {$flag->value}");
        }
        $this->assertTrue(Package::findBySlug('custom')->is_contact_only);
    }
}
