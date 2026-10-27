<?php

namespace Tests\Feature\Portal;

use App\Enums\Feature;
use App\Models\Package;
use App\Services\Portal\PricingCards;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The AI's uses a day are a dashboard matter: the public page, which claims
 * no AI, never shows them, whatever the packages say; the dashboard's own
 * Package page reads them from /api/packages.
 */
class WritingHelpPricingTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** Gives a package an AI budget a month, in cents (null for no cap). */
    private function give(string $slug, ?float $cents): void
    {
        Package::findBySlug($slug)->update(['ai_budget_cents' => $cents]);
    }

    public function test_the_pricing_cards_never_list_it_however_the_packages_differ(): void
    {
        $this->give('free', 0);
        $this->give('pro', 75);
        $this->give('premium', 150);
        $this->give('custom', null);

        $lines = collect(app(PricingCards::class)->all())->pluck('lines')->flatten()->implode(' | ');

        $this->assertStringNotContainsString('AI', $lines);
        $this->assertStringNotContainsString('writing', $lines);
        $this->assertStringNotContainsString('credit', app(PricingCards::class)->fairUseNote() ?? '');
    }

    public function test_a_package_that_only_grows_its_ai_credits_still_builds_on_the_one_before(): void
    {
        // Pro has everything Free has; more AI credits must not break "Everything in Free, plus".
        $this->give('free', 5);
        $this->give('pro', 100);

        $pro = collect(app(PricingCards::class)->all())->firstWhere('name', 'Pro');

        $this->assertSame('Free', $pro['base']);
    }

    public function test_the_landing_page_says_no_ai_in_english_or_arabic(): void
    {
        $this->give('pro', 100);
        $this->give('premium', 200);

        $english = (string) $this->get('/')->assertOk()->getContent();
        $arabic = (string) $this->get('/ar')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/\bAI\b/', preg_replace('/(name="csrf-token" content=|name="_token" value=)"[^"]*"/', '', $english));
        $this->assertStringNotContainsString('الذكاء الاصطناعي', $arabic);
    }

    public function test_the_dashboards_packages_carry_each_packages_credits_and_no_daily_uses(): void
    {
        $owner = $this->owner();

        $packages = collect($this->actingAs($owner->user)->getJson(route('api.packages.index'))->assertOk()->json('data'))->keyBy('slug');

        $this->assertSame(500, $packages['free']['features']['writing_help_credits']);
        $this->assertSame(7500, $packages['pro']['features']['writing_help_credits']);
        $this->assertArrayNotHasKey('writing_help_limit', $packages['pro']['features']);
    }

    public function test_the_feature_names_itself_for_the_admin_in_both_languages(): void
    {
        app()->setLocale('en');
        $this->assertSame('AI credits a month', Feature::WritingHelpCredits->label());
        app()->setLocale('ar');
        $this->assertSame('أرصدة الذكاء الاصطناعي شهريًا', Feature::WritingHelpCredits->label());
    }
}
