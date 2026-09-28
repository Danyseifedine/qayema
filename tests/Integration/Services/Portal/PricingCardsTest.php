<?php

namespace Tests\Integration\Services\Portal;

use App\Models\Package;
use App\Services\Portal\PricingCards;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The landing page's pricing is read from the packages, so it says exactly
 * what the seeded catalogue (config/package.php) holds, and follows an admin's
 * edits without anyone touching the page.
 */
class PricingCardsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array<string, mixed>> the cards by package name */
    private function cards(): array
    {
        $cards = app(PricingCards::class)->all();

        return array_combine(array_column($cards, 'name'), $cards);
    }

    public function test_the_four_packages_come_in_their_order_with_what_each_holds(): void
    {
        $cards = app(PricingCards::class)->all();

        $this->assertSame(['Free', 'Pro', 'Premium', 'Custom'], array_column($cards, 'name'));

        [$free, $pro, $premium, $custom] = $cards;

        $this->assertSame('Free', $free['price']);
        $this->assertNull($free['per']);
        $this->assertTrue($free['free']);
        $this->assertNull($free['base']);
        $this->assertSame(['40 dishes', '8 categories', '1 social link'], $free['lines']);

        $this->assertSame('$12', $pro['price']);
        $this->assertSame('/ month', $pro['per']);
        $this->assertFalse($pro['featured']);
        $this->assertSame('Free', $pro['base']);
        $this->assertSame(
            ['150 dishes', '15 categories', '2 social links', 'Second menu language', 'Your colours and fonts', 'Analytics'],
            $pro['lines'],
        );

        $this->assertSame('$29', $premium['price']);
        $this->assertTrue($premium['featured']);
        $this->assertSame('Pro', $premium['base']);
        $this->assertSame(
            ['500 dishes', '30 categories', '10 social links', 'Premium designs', 'QR studio', 'Orders on WhatsApp', 'Advanced analytics'],
            $premium['lines'],
        );

        $this->assertSame("Let's talk", $custom['price']);
        $this->assertNull($custom['per']);
        $this->assertTrue($custom['contact']);
        $this->assertSame('Premium', $custom['base']);
        $this->assertSame(['Unlimited dishes', 'Unlimited categories', 'Unlimited social links'], $custom['lines']);
    }

    public function test_each_feature_is_unlocked_by_the_first_package_that_has_it(): void
    {
        $this->assertSame([
            'multiple_languages' => 'Pro',
            'appearance' => 'Pro',
            'premium_designs' => 'Premium',
            'qr_studio' => 'Premium',
            'ordering' => 'Premium',
            'analytics' => 'Pro',
            'advanced_analytics' => 'Premium',
        ], app(PricingCards::class)->unlockedBy());

        // A feature Free has needs no chip.
        $free = Package::findBySlug('free');
        $free->update(['features' => [...$free->features, 'analytics' => 1]]);
        $this->assertArrayNotHasKey('analytics', app(PricingCards::class)->unlockedBy());
    }

    public function test_an_admins_edit_shows_on_the_page_without_touching_it(): void
    {
        $pro = Package::findBySlug('pro');
        $pro->update([
            'price_cents' => 1550,
            'features' => [...$pro->features, 'dish_limit' => 200, 'qr_studio' => 1],
        ]);

        $card = $this->cards()['Pro'];

        $this->assertSame('$15.50', $card['price']);
        $this->assertContains('200 dishes', $card['lines']);
        $this->assertContains('QR studio', $card['lines']);
        // Premium no longer adds the QR studio: Pro already has it.
        $this->assertNotContains('QR studio', $this->cards()['Premium']['lines']);
    }

    public function test_a_package_that_drops_something_lists_everything_it_includes(): void
    {
        // "Everything in Pro, plus" would be false once Premium lacks a Pro
        // feature, so the card lists all it includes instead.
        $premium = Package::findBySlug('premium');
        $premium->update(['features' => [...$premium->features, 'analytics' => 0]]);

        $card = $this->cards()['Premium'];

        $this->assertNull($card['base']);
        $this->assertContains('500 dishes', $card['lines']);
        $this->assertContains('Second menu language', $card['lines']);
        $this->assertNotContains('Analytics', $card['lines']);
    }

    public function test_arabic_uses_the_arabic_names_and_plural_forms(): void
    {
        app()->setLocale('ar');

        $cards = app(PricingCards::class)->all();

        $this->assertSame(['مجاني', 'برو', 'مميّز', 'مخصّص'], array_column($cards, 'name'));
        $this->assertSame('مجانية', $cards[0]['price']);
        $this->assertSame(['40 طبقاً', '8 أقسام', 'رابط تواصل واحد'], $cards[0]['lines']);
        $this->assertSame('150 طبقاً', $cards[1]['lines'][0]);
        $this->assertSame('500 طبق', $cards[2]['lines'][0]);
        $this->assertSame('لنتحدّث', $cards[3]['price']);
        // A price reads the same in both languages.
        $this->assertSame('$12', $cards[1]['price']);
    }
}
