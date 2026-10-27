<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Filament\Admin\Pages\AiCredits;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Packages\Pages\ListPackages;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Widgets\WritingHelpUsage;
use App\Models\AppSetting;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Models\WritingHelpDay;
use App\Services\Ai\WritingHelpCredits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The AI credits as the admin runs them: what a credit is worth and each
 * action's price (Settings, AI credits), a package's AI budget in dollars
 * with the credits it gives, one restaurant's own number, and what they
 * really cost.
 */
class AiCreditsAdminTest extends TestCase
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
        $this->actingAs($this->admin());
    }

    public function test_the_settings_page_opens_on_the_defaults(): void
    {
        $this->get(AiCredits::getUrl())->assertOk()->assertSee('What a credit is worth');

        Livewire::test(AiCredits::class)
            ->assertSchemaStateSet(['credit_cents' => 0.01, 'translation' => 1, 'description' => 3], 'form')
            ->assertSee('$1 buys 10,000 credits.');
    }

    public function test_saving_moves_every_packages_credits_but_not_a_restaurants_own(): void
    {
        $onPro = $this->ownerOn('pro');
        $ownNumber = $this->ownerOn('pro');
        FeatureGrant::factory()->for($ownNumber)->forFeature(Feature::WritingHelpCredits, 20000)->create(['mode' => GrantMode::Set]);
        $this->assertSame(7500, app(WritingHelpCredits::class)->limit($onPro));

        Livewire::test(AiCredits::class)
            ->fillForm(['credit_cents' => 0.02, 'translation' => 2, 'description' => 6], 'form')
            ->call('save')
            ->assertHasNoFormErrors([], 'form')
            ->assertNotified('Saved');

        $this->assertSame(0.02, AppSetting::read('ai_credit_cents'));
        $this->assertSame(6, AppSetting::read('ai_credits_description'));
        $this->assertSame(3750, app(WritingHelpCredits::class)->limit($onPro->fresh()));
        $this->assertSame(20000, app(WritingHelpCredits::class)->limit($ownNumber->fresh()));
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function refused(): array
    {
        return [
            'a credit worth nothing' => [['credit_cents' => 0], 'credit_cents'],
            'no worth at all' => [['credit_cents' => null], 'credit_cents'],
            'a price below none' => [['translation' => -1], 'translation'],
            'a price that is not whole' => [['description' => 2.5], 'description'],
        ];
    }

    /** @param  array<string, mixed>  $typed */
    #[DataProvider('refused')]
    public function test_a_setting_out_of_range_is_refused(array $typed, string $field): void
    {
        Livewire::test(AiCredits::class)
            ->fillForm($typed, 'form')
            ->call('save')
            ->assertHasFormErrors([$field], 'form');

        $this->assertDatabaseCount('app_settings', 0);
    }

    public function test_the_page_shows_what_a_credit_and_each_action_really_cost(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00', 'UTC'));
        WritingHelpDay::factory()->for($this->owner())->create([
            'day' => '2026-10-19', 'uses' => 5, 'credits' => 7, 'cost' => '0.000560',
            'translation_uses' => 4, 'translation_cost' => '0.000260', 'description_uses' => 1, 'description_cost' => '0.000300',
        ]);

        Livewire::test(AiCredits::class)
            ->assertSee('$0.00008 (worth $0.0001)')
            ->assertSee('$0.000065 each, 0.65 credits&#039; worth (priced at 1)', false)
            ->assertSee('$0.0003 each, 3 credits&#039; worth (priced at 3)', false);
    }

    public function test_the_page_with_nothing_used_yet(): void
    {
        Livewire::test(AiCredits::class)->assertSee('None yet (worth $0.0001)')->assertSee('None yet');
    }

    public function test_a_package_is_given_dollars_and_shows_the_credits_they_buy(): void
    {
        Livewire::test(EditPackage::class, ['record' => Package::findBySlug('pro')->id])
            ->assertFormSet(['ai_budget_cents' => 0.75])
            ->assertSee('= 7,500 AI credits a month (a credit is worth 0.01¢, set in Settings, AI credits). Owners see the credits, never the dollars.')
            ->assertFormFieldDoesNotExist('features.writing_help_credits')
            ->assertFormFieldExists('fair_use', fn ($field): bool => ! array_key_exists('writing_help_credits', $field->getOptions()));
    }

    /** @return array<string, array{0: float|int|string|null, 1: float|null, 2: string}> */
    public static function budgets(): array
    {
        return [
            'dollars and cents' => [1.5, 150.0, '= 15,000 AI credits a month'],
            'typed as text' => ['0.25', 25.0, '= 2,500 AI credits a month'],
            'nothing, which turns it off' => [0, 0.0, 'No AI credits: the AI buttons are off on this package.'],
            'empty, for no cap' => [null, null, 'No cap: unlimited AI credits'],
        ];
    }

    #[DataProvider('budgets')]
    public function test_an_admin_sets_a_packages_ai_budget(float|int|string|null $typed, ?float $cents, string $said): void
    {
        $pro = Package::findBySlug('pro');

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->fillForm(['ai_budget_cents' => $typed])
            ->assertSee($said)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($cents, $pro->fresh()->ai_budget_cents);
    }

    public function test_a_budget_below_nothing_is_refused(): void
    {
        Livewire::test(EditPackage::class, ['record' => Package::findBySlug('pro')->id])
            ->fillForm(['ai_budget_cents' => -1])
            ->call('save')
            ->assertHasFormErrors(['ai_budget_cents']);
    }

    public function test_the_packages_table_shows_each_packages_credits(): void
    {
        Livewire::test(ListPackages::class)
            ->assertTableColumnStateSet('feature_writing_help_credits', '7500', Package::findBySlug('pro'))
            ->assertTableColumnStateSet('feature_writing_help_credits', '15000', Package::findBySlug('premium'));
    }

    public function test_one_restaurant_is_given_its_own_credits_and_sees_what_they_are_worth(): void
    {
        $restaurant = $this->ownerOn('pro');

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertFormSet(['features.writing_help_credits' => 7500])
            ->assertSee('Package: 7,500. Worth $0.75 of AI a month')
            ->fillForm(['features.writing_help_credits' => 20000])
            ->assertSee('Worth $2.00 of AI a month')
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(20000, app(WritingHelpCredits::class)->limit($restaurant->fresh()));
        $grant = FeatureGrant::query()->where('restaurant_id', $restaurant->id)->sole();
        $this->assertSame([Feature::WritingHelpCredits, GrantMode::Set, 20000], [$grant->feature, $grant->mode, $grant->value]);

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->fillForm(['features.writing_help_credits' => null])
            ->assertSee('No cap on its AI a month');
    }

    public function test_the_restaurant_page_and_the_admin_app_show_this_months_credits(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00', 'UTC'));
        $restaurant = $this->ownerOn('pro', ['timezone' => 'UTC']);
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-10-19', 'credits' => 1240, 'cost' => '0.090000']);

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertSee('1,240 of 7,500 taken ($0.09 real cost, worth $0.75)');

        $this->withToken($this->admin()->createToken('phone')->plainTextToken)
            ->getJson("/api/admin/restaurants/{$restaurant->id}")
            ->assertJsonPath('data.stats.ai_credits', ['limit' => 7500, 'used' => 1240, 'left' => 6260, 'cost' => 0.09, 'worth' => 0.75]);

        Package::findBySlug('pro')->update(['ai_budget_cents' => null]);
        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])->assertSee('1,240 taken, no cap ($0.09 real cost)');
    }

    public function test_the_home_page_says_what_a_credit_really_costs(): void
    {
        $this->travelTo(Carbon::parse('2026-10-20 12:00', 'UTC'));

        Livewire::test(WritingHelpUsage::class)->assertSee('None yet (a credit is worth $0.0001)');

        WritingHelpDay::factory()->for($this->owner())->create(['day' => '2026-10-19', 'credits' => 7, 'cost' => '0.000560']);
        Livewire::test(WritingHelpUsage::class)->assertSee('AI credits used')->assertSee('Really $0.00008 a credit (worth $0.0001)');
    }
}
