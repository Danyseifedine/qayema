<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Packages\Pages\ListPackages;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\FeatureGrantsRelationManager;
use App\Filament\Admin\Widgets\WritingHelpUsage;
use App\Models\Package;
use App\Models\WritingHelpDay;
use App\Services\Ai\WritingHelpUses;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The AI's uses a day (Feature::WritingHelpLimit) as an admin sets them at
 * /admin: per package, per restaurant with a grant, and what they read back.
 */
class WritingHelpLimitAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.writing_help.provider' => 'anthropic',
            'ai.providers.anthropic.key' => 'test-key',
        ]);
        $this->actingAs($this->admin());
    }

    public function test_the_package_form_has_the_field_with_its_label_and_hint(): void
    {
        Livewire::test(EditPackage::class, ['record' => Package::findBySlug('pro')->id])
            ->assertFormFieldExists('features.writing_help_limit', fn ($field): bool => $field->getLabel() === 'AI writing help a day')
            ->assertSee('How many times a day the dashboard&#039;s AI may translate or write text for the restaurant. 0 turns it off. Leave empty for unlimited.', false)
            ->assertFormSet(['features.writing_help_limit' => 100]);
    }

    /** @return array<string, array{0: int|string|null, 1: int|null}> */
    public static function saved(): array
    {
        return [
            'a number' => [100, 100],
            'a number typed as text' => ['200', 200],
            'none, which turns it off' => [0, 0],
            'empty, for no daily cap' => [null, null],
            'an empty string, for no daily cap' => ['', null],
            'the most the form takes' => [100000, 100000],
        ];
    }

    #[DataProvider('saved')]
    public function test_an_admin_sets_a_packages_uses_a_day(int|string|null $typed, ?int $stored): void
    {
        $pro = Package::findBySlug('pro');

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->fillForm(['features.writing_help_limit' => $typed])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($stored, $pro->fresh()->features['writing_help_limit']);
    }

    /** @return array<string, array{0: int}> */
    public static function refused(): array
    {
        return ['below none' => [-1], 'past the most' => [100001]];
    }

    #[DataProvider('refused')]
    public function test_a_number_out_of_range_is_refused(int $typed): void
    {
        $pro = Package::findBySlug('pro');

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->fillForm(['features.writing_help_limit' => $typed])
            ->call('save')
            ->assertHasFormErrors(['features.writing_help_limit']);

        $this->assertSame(100, $pro->fresh()->features['writing_help_limit']);
    }

    public function test_a_new_number_reaches_the_owners_at_once(): void
    {
        $onPro = $this->ownerOn('pro');
        $onFree = $this->owner();
        $this->assertSame(100, app(WritingHelpUses::class)->limit($onPro));

        Livewire::test(EditPackage::class, ['record' => Package::findBySlug('pro')->id])
            ->fillForm(['features.writing_help_limit' => 150])
            ->call('save');

        $this->assertSame(150, app(WritingHelpUses::class)->limit($onPro->fresh()));
        $this->assertSame(10, app(WritingHelpUses::class)->limit($onFree->fresh()), 'Another package is untouched.');
        $this->actingAs($onPro->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.writing_help', ['limit' => 150, 'left' => 150]);
    }

    public function test_setting_none_takes_the_buttons_away(): void
    {
        $onPro = $this->ownerOn('pro');

        Livewire::test(EditPackage::class, ['record' => Package::findBySlug('pro')->id])
            ->fillForm(['features.writing_help_limit' => 0])
            ->call('save');

        $this->actingAs($onPro->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.writing_help', null);
    }

    public function test_the_fair_use_choices_list_it_like_every_limit(): void
    {
        Livewire::test(EditPackage::class, ['record' => Package::findBySlug('pro')->id])
            ->assertFormFieldExists('fair_use', fn ($field): bool => array_key_exists('writing_help_limit', $field->getOptions()));
    }

    public function test_the_packages_table_shows_each_packages_uses(): void
    {
        Package::findBySlug('custom')->update(['features' => [...(array) Package::findBySlug('custom')->features, 'writing_help_limit' => null]]);

        Livewire::test(ListPackages::class)
            ->assertTableColumnExists('feature_writing_help_limit')
            ->assertTableColumnStateSet('feature_writing_help_limit', '10', Package::findBySlug('free'))
            ->assertTableColumnStateSet('feature_writing_help_limit', '100', Package::findBySlug('pro'))
            ->assertTableColumnStateSet('feature_writing_help_limit', '∞', Package::findBySlug('custom'));
    }

    public function test_a_grant_gives_one_restaurant_more_uses(): void
    {
        $restaurant = $this->owner();
        $untouched = $this->owner();

        Livewire::test(FeatureGrantsRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'feature' => Feature::WritingHelpLimit->value,
                'value' => 40,
                'source' => 'admin',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(50, app(WritingHelpUses::class)->limit($restaurant->fresh()), '10 on Free + 40 granted.');
        $this->assertSame(10, app(WritingHelpUses::class)->limit($untouched->fresh()));
    }

    public function test_the_restaurant_page_says_what_it_gets_now(): void
    {
        $restaurant = $this->ownerOn('pro');

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertSee('AI writing help a day 100');
    }

    public function test_the_restaurant_page_says_how_much_ai_help_it_used(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 12:00', 'UTC'));
        $restaurant = $this->ownerOn('pro', ['timezone' => 'UTC']);
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-10-11', 'uses' => 3, 'input_tokens' => 1200, 'output_tokens' => 100]);
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-10-01', 'uses' => 38, 'input_tokens' => 50000, 'output_tokens' => 1000]);

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertSee('AI writing help used')
            ->assertSee('3 today, 41 in the last 30 days (52,300 tokens)');
    }

    public function test_a_restaurant_that_never_used_it_says_so(): void
    {
        Livewire::test(EditRestaurant::class, ['record' => $this->owner()->id])
            ->assertSee('Not used in the last 30 days.');
    }

    public function test_the_dashboard_shows_everyones_use_over_thirty_days(): void
    {
        WritingHelpDay::factory()->for($this->owner())->create(['uses' => 4, 'input_tokens' => 1000, 'output_tokens' => 200]);
        WritingHelpDay::factory()->for($this->owner())->create(['uses' => 2, 'input_tokens' => 500, 'output_tokens' => 100]);

        Livewire::test(WritingHelpUsage::class)
            ->assertSee('AI writing help, last 30 days')
            ->assertSeeInOrder(['Uses', '6', 'Tokens', '1,800', 'Restaurants', '2']);
    }

    public function test_the_dashboard_carries_the_ai_card(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertSeeLivewire(WritingHelpUsage::class);
    }

    public function test_the_restaurants_table_shows_each_ones_ai_uses_over_thirty_days(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 12:00', 'UTC'));
        $busy = $this->owner();
        $quiet = $this->owner();
        $never = $this->owner();
        WritingHelpDay::factory()->for($busy)->create(['day' => '2026-10-11', 'uses' => 30, 'input_tokens' => 40000, 'output_tokens' => 2000]);
        WritingHelpDay::factory()->for($busy)->create(['day' => '2026-09-25', 'uses' => 12, 'input_tokens' => 9000, 'output_tokens' => 500]);
        // Older than thirty days: out of the column.
        WritingHelpDay::factory()->for($busy)->create(['day' => '2026-09-01', 'uses' => 500]);
        WritingHelpDay::factory()->for($quiet)->create(['day' => '2026-10-10', 'uses' => 2, 'input_tokens' => 800, 'output_tokens' => 60]);

        Livewire::test(ListRestaurants::class)
            ->assertTableColumnExists('writing_help_uses')
            ->assertTableColumnStateSet('writing_help_uses', 42, $busy)
            ->assertTableColumnStateSet('writing_help_uses', 2, $quiet)
            ->assertTableColumnStateSet('writing_help_uses', 0, $never)
            ->assertSee('51,500 tokens')
            ->assertSee('860 tokens');
    }

    public function test_the_heaviest_ai_users_come_first_when_sorted(): void
    {
        $light = $this->owner();
        $heavy = $this->owner();
        $none = $this->owner();
        WritingHelpDay::factory()->for($light)->create(['uses' => 3]);
        WritingHelpDay::factory()->for($heavy)->create(['uses' => 90]);

        Livewire::test(ListRestaurants::class)
            ->sortTable('writing_help_uses', 'desc')
            ->assertCanSeeTableRecords([$heavy, $light, $none], inOrder: true)
            ->sortTable('writing_help_uses', 'asc')
            ->assertCanSeeTableRecords([$none, $light, $heavy], inOrder: true);
    }

    public function test_the_ai_column_adds_no_query_per_row(): void
    {
        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::test(ListRestaurants::class);
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };
        WritingHelpDay::factory()->for($this->owner())->create();
        $few = $count();

        foreach (range(1, 6) as $n) {
            WritingHelpDay::factory()->for($this->owner())->create();
        }

        // As AdminListQueriesTest allows: the hidden dish limit's package, one a row.
        $this->assertLessThanOrEqual($few + 6, $count());
    }
}
