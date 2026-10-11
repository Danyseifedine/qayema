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

    private function give(string $slug, ?int $uses, bool $fairUse = false): void
    {
        $package = Package::findBySlug($slug);
        $package->update([
            'features' => [...(array) $package->features, 'writing_help_limit' => $uses],
            'fair_use' => $fairUse ? [...(array) $package->fair_use, 'writing_help_limit'] : $package->fair_use,
        ]);
    }

    public function test_the_pricing_cards_never_list_it_however_the_packages_differ(): void
    {
        $this->give('free', 0);
        $this->give('pro', 100);
        $this->give('premium', 200, fairUse: true);
        $this->give('custom', null);

        $lines = collect(app(PricingCards::class)->all())->pluck('lines')->flatten()->implode(' | ');

        $this->assertStringNotContainsString('AI', $lines);
        $this->assertStringNotContainsString('writing', $lines);
        $this->assertStringNotContainsString('200', app(PricingCards::class)->fairUseNote() ?? '');
    }

    public function test_a_package_that_only_grows_its_ai_uses_still_builds_on_the_one_before(): void
    {
        // Pro has everything Free has; more AI uses must not break "Everything in Free, plus".
        $this->give('free', 5);
        $this->give('pro', 100);

        $pro = collect(app(PricingCards::class)->all())->firstWhere('name', 'Pro');

        $this->assertSame('Free', $pro['base']);
    }

    public function test_the_landing_page_says_no_ai_in_english_or_arabic(): void
    {
        $this->give('pro', 100);
        $this->give('premium', 200, fairUse: true);

        $english = (string) $this->get('/')->assertOk()->getContent();
        $arabic = (string) $this->get('/ar')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/\bAI\b/', preg_replace('/(name="csrf-token" content=|name="_token" value=)"[^"]*"/', '', $english));
        $this->assertStringNotContainsString('الذكاء الاصطناعي', $arabic);
    }

    public function test_the_dashboards_packages_carry_each_packages_uses(): void
    {
        $this->give('pro', 100);
        $this->give('custom', null);
        $owner = $this->owner();

        $packages = collect($this->actingAs($owner->user)->getJson(route('api.packages.index'))->assertOk()->json('data'))->keyBy('slug');

        $this->assertSame(10, $packages['free']['features']['writing_help_limit']);
        $this->assertSame(100, $packages['pro']['features']['writing_help_limit']);
        $this->assertNull($packages['custom']['features']['writing_help_limit']);
    }

    public function test_the_feature_names_itself_for_the_admin_in_both_languages(): void
    {
        app()->setLocale('en');
        $this->assertSame('AI writing help a day', Feature::WritingHelpLimit->label());
        app()->setLocale('ar');
        $this->assertSame('المساعدة في الكتابة بالذكاء الاصطناعي يوميًا', Feature::WritingHelpLimit->label());
        $this->assertStringContainsString('القيمة 0 توقفه', Feature::WritingHelpLimit->hint());
    }
}
