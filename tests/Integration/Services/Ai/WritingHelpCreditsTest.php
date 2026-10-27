<?php

namespace Tests\Integration\Services\Ai;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Enums\WritingHelpAction;
use App\Models\AppSetting;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Models\WritingHelpDay;
use App\Services\Ai\WritingHelp;
use App\Services\Ai\WritingHelpCredits;
use App\Services\Packages\Entitlements;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Services\Ai\WritingHelpCredits: a package's AI budget as credits a
 * month at what the admin says a credit is worth, one restaurant's own
 * number or extra, the month counted from the 1st in its timezone, and the
 * cap that keeps the real cost within what the credits are worth.
 */
class WritingHelpCreditsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.writing_help.provider' => 'anthropic',
            'services.writing_help.model' => 'claude-haiku-5-5',
            'ai.providers.anthropic.key' => 'test-key',
        ]);
    }

    private function credits(): WritingHelpCredits
    {
        return app(WritingHelpCredits::class);
    }

    public function test_a_credit_is_a_hundredth_of_a_cent_until_the_admin_says_otherwise(): void
    {
        $this->assertSame(0.01, WritingHelpCredits::creditCents());
        $this->assertSame(1, WritingHelpAction::Translation->credits());
        $this->assertSame(3, WritingHelpAction::Description->credits());
        $this->assertSame(7500, WritingHelpCredits::forBudget(75));
        $this->assertSame(0.75, WritingHelpCredits::dollarsOf(7500));
        $this->assertNull(WritingHelpCredits::forBudget(null));

        AppSetting::write(['ai_credit_cents' => 0.02, 'ai_credits_translation' => 2, 'ai_credits_description' => 5]);

        $this->assertSame(3750, WritingHelpCredits::forBudget(75));
        $this->assertSame(2, WritingHelpAction::Translation->credits());
        $this->assertSame(5, WritingHelpAction::Description->credits());
    }

    public function test_a_packages_credits_are_its_budget_at_a_credits_worth_and_follow_both(): void
    {
        $pro = Package::findBySlug('pro');
        $this->assertSame(7500, $pro->featureValue(Feature::WritingHelpCredits));

        AppSetting::write(['ai_credit_cents' => 0.015]);
        $this->assertSame(5000, $pro->featureValue(Feature::WritingHelpCredits));

        $pro->update(['ai_budget_cents' => null]);
        $this->assertNull($pro->featureValue(Feature::WritingHelpCredits));
    }

    public function test_one_restaurant_may_have_its_own_number_or_extra(): void
    {
        $owner = $this->ownerOn('pro');
        $this->assertSame(7500, $this->credits()->limit($owner));

        FeatureGrant::factory()->for($owner)->forFeature(Feature::WritingHelpCredits, 2500)->create(['mode' => GrantMode::Add]);
        Entitlements::flush($owner->id);
        $this->assertSame(10000, $this->credits()->limit($owner->fresh()));

        FeatureGrant::factory()->for($owner)->forFeature(Feature::WritingHelpCredits, 20000)->create(['mode' => GrantMode::Set]);
        Entitlements::flush($owner->id);
        $this->assertSame(20000, $this->credits()->limit($owner->fresh()));
    }

    public function test_the_month_counts_from_the_first_in_the_restaurants_timezone(): void
    {
        // 22:30 UTC on 31 October is already 1 November in Beirut (UTC+2).
        $this->travelTo(Carbon::parse('2026-10-31 22:30', 'UTC'));
        $owner = $this->ownerOn('pro', ['timezone' => 'Asia/Beirut']);
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-11-01', 'credits' => 40, 'cost' => '0.003000']);
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-31', 'credits' => 900, 'cost' => '0.090000']);
        WritingHelpDay::factory()->create(['day' => '2026-11-01', 'credits' => 70]);

        $this->assertSame(['credits' => 40, 'cost' => 0.003], $this->credits()->thisMonth($owner));
        $this->assertSame(7460, $this->credits()->left($owner));
        $this->assertSame('2026-12-01', $this->credits()->resetsOn($owner)->toDateString());
    }

    public function test_an_action_runs_only_while_its_credits_and_their_worth_cover_it(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00', 'UTC'));
        $owner = $this->ownerOn('free', ['timezone' => 'UTC']);
        // 500 credits worth $0.05: 498 left, $0.0010 of real cost spent.
        $day = WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-20', 'credits' => 2, 'cost' => '0.001000']);

        $this->assertNull($this->credits()->refusal($owner, WritingHelpAction::Description, 0.0005));

        // 2 left: a translation (1) goes, a description (3) does not.
        $day->update(['credits' => 498]);
        $this->assertNull($this->credits()->refusal($owner, WritingHelpAction::Translation, 0.0005));
        $this->assertSame('credits', $this->credits()->refusal($owner, WritingHelpAction::Description, 0.0005));

        // Credits left, but the most it could cost passes what is left of their worth.
        $day->update(['credits' => 400, 'cost' => '0.049000']);
        $this->assertSame('cost', $this->credits()->refusal($owner, WritingHelpAction::Translation, 0.0011));

        $day->update(['credits' => 500, 'cost' => '0.001000']);
        $this->assertSame('credits', $this->credits()->refusal($owner, WritingHelpAction::Translation, 0.0));

        // No cap at all, nothing is held back.
        Package::findBySlug('free')->update(['ai_budget_cents' => null]);
        $this->assertNull($this->credits()->refusal($owner->fresh(), WritingHelpAction::Description, 9.0));
    }

    public function test_never_more_credits_are_left_than_the_money_left_buys(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00', 'UTC'));
        $owner = $this->ownerOn('free', ['timezone' => 'UTC']);
        // 100 of 500 taken, but $0.049 of the $0.05 spent: $0.001 buys 10.
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-20', 'credits' => 100, 'cost' => '0.049000']);

        $this->assertSame(10, $this->credits()->left($owner));
        $this->assertSame(10, $this->credits()->forAdmin($owner)['left']);
        // Ten still cover a description, which costs 3.
        $this->assertNull($this->credits()->refusal($owner, WritingHelpAction::Description, 0.0001));
    }

    public function test_a_route_not_named_is_priced_as_the_dearer_action(): void
    {
        $this->assertSame(WritingHelpAction::Translation, WritingHelpAction::fromRoute('api.writing.translate-many'));
        $this->assertSame(WritingHelpAction::Description, WritingHelpAction::fromRoute('api.writing.a-new-kind'));
    }

    public function test_what_the_dashboard_and_the_admin_are_told(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00', 'UTC'));
        $owner = $this->ownerOn('pro', ['timezone' => 'UTC']);
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-20', 'credits' => 1240, 'cost' => '0.090000']);

        $this->assertSame([
            'limit' => 7500,
            'left' => 6260,
            'resets_on' => '2026-11-01',
            'prices' => ['translation' => 1, 'description' => 3],
        ], $this->credits()->forDashboard($owner));
        $this->assertSame(['limit' => 7500, 'used' => 1240, 'left' => 6260, 'cost' => 0.09, 'worth' => 0.75], $this->credits()->forAdmin($owner));

        Package::findBySlug('pro')->update(['ai_budget_cents' => null]);
        $this->assertSame(['limit' => null, 'used' => 1240, 'left' => null, 'cost' => 0.09, 'worth' => null], $this->credits()->forAdmin($owner->fresh()));
    }

    public function test_the_most_a_request_could_cost_is_every_try_at_its_cap(): void
    {
        $help = app(WritingHelp::class);

        // A 5-character name for a 60-character field: an answer of 220
        // tokens at most, and a prompt of 1,500 + 10 + the last answer back.
        // Three tries at $0.10 in and $0.50 out per million.
        $this->assertEqualsWithDelta(3 * (1730 * 0.10 + 220 * 0.50) / 1_000_000, $help->mostItCouldCost('api.writing.translate', ['text' => 'Kebab', 'max' => 60]), 1e-12);

        // A description it may keep to: the longer of the two.
        $this->assertGreaterThan(
            $help->mostItCouldCost('api.writing.translate', ['text' => 'Soups', 'max' => 40]),
            $help->mostItCouldCost('api.writing.translate', ['text' => 'Soups', 'max' => 40, 'fit' => 'restaurant']),
        );

        // Two directions are two calls; nonsense is kept to what the checks allow.
        $one = $help->mostItCouldCost('api.writing.translate-many', ['items' => [['text' => 'Kebab', 'from' => 'en', 'to' => 'ar', 'max' => 60]]]);
        $two = $help->mostItCouldCost('api.writing.translate-many', ['items' => [
            ['text' => 'Kebab', 'from' => 'en', 'to' => 'ar', 'max' => 60],
            ['text' => 'كباب', 'from' => 'ar', 'to' => 'en', 'max' => 60],
        ]]);
        $this->assertGreaterThan($one, $two);
        $this->assertGreaterThan(0, $help->mostItCouldCost('api.writing.translate-many', ['items' => [['from' => ['x'], 'to' => 5, 'text' => ['y'], 'max' => 'a lot'], 'other']]));
        $this->assertGreaterThan(0, $help->mostItCouldCost('api.writing.translate', ['text' => ['y'], 'fit' => ['x']]));

        // The restaurant's reads its menu, so it can cost more than a category's.
        $this->assertGreaterThan(
            $help->mostItCouldCost('api.writing.category-description', []),
            $help->mostItCouldCost('api.writing.restaurant-description', []),
        );

        $this->assertSame(0.0, $help->mostItCouldCost('api.dishes.store', []));

        config(['services.writing_help.model' => 'a-model-with-no-price']);
        $this->assertSame(0.0, $help->mostItCouldCost('api.writing.translate', ['text' => 'Kebab']));
    }

    public function test_settings_are_read_once_and_a_save_is_read_at_once(): void
    {
        $this->assertSame('fallback', AppSetting::read('missing', 'fallback'));

        AppSetting::write(['ai_credit_cents' => 0.05]);
        $this->assertSame(0.05, AppSetting::read('ai_credit_cents'));

        AppSetting::write(['ai_credit_cents' => null]);
        $this->assertNull(AppSetting::read('ai_credit_cents'));
        $this->assertSame(0.01, WritingHelpCredits::creditCents());
        $this->assertDatabaseMissing('app_settings', ['key' => 'ai_credit_cents']);
    }
}
