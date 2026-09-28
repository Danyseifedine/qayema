<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Actions\ExtendPackageAction;
use App\Filament\Admin\Concerns\KeepsTranslations;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Models\Category;
use App\Models\Package;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * The shared admin actions and concerns at their edges: what the Extend
 * modal says for each kind of end, and how KeepsTranslations merges what a
 * form sends over what it does not show.
 */
class AdminActionsEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_extend_modal_names_the_package_and_the_upcoming_end(): void
    {
        $restaurant = $this->ownerOn('pro', ['name' => ['en' => 'Olive'], 'package_ends_at' => now()->addDays(5)]);
        $action = ExtendPackageAction::make()->record($restaurant);

        $this->assertSame('Extend Pro for Olive', $action->getModalHeading());
        $this->assertSame('It ends on '.now()->addDays(5)->toFormattedDayDateString().'.', $action->getModalDescription());
    }

    public function test_the_extend_modal_says_when_a_package_ended(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_started_at' => now()->subYear(), 'package_ends_at' => now()->subDays(2)]);

        $this->assertSame(
            'It ended on '.now()->subDays(2)->toFormattedDayDateString().'.',
            ExtendPackageAction::make()->record($restaurant)->getModalDescription(),
        );
    }

    /**
     * The row hides Extend for a package with no end, so this wording is
     * only reached when the action is built for such a record directly.
     */
    public function test_the_extend_modal_says_a_forever_package_runs_forever(): void
    {
        $restaurant = $this->ownerOn('pro');
        $action = ExtendPackageAction::make()->record($restaurant);

        $this->assertSame('It runs forever today.', $action->getModalDescription());
        $this->assertFalse($action->isVisible());
    }

    public function test_extending_an_ended_package_counts_from_today_and_keeps_the_note(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_started_at' => now()->subYear(), 'package_ends_at' => now()->subMonth()]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->callAction(TestAction::make('extendPackage')->table($restaurant), data: ['extend_by' => 3, 'note' => 'Renewed late.'])
            ->assertHasNoFormErrors()
            ->assertNotified('Package extended');

        $restaurant = $restaurant->fresh();
        $this->assertTrue($restaurant->package_ends_at->isSameDay(now()->addMonthsNoOverflow(3)));
        $this->assertFalse($restaurant->packageExpired());
        $this->assertSame('Renewed late.', $restaurant->packageChanges()->latest('id')->first()->note);
    }

    public function test_extend_refuses_an_overlong_note(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(5)]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->callAction(TestAction::make('extendPackage')->table($restaurant), data: ['extend_by' => 1, 'note' => str_repeat('n', 256)])
            ->assertHasFormErrors(['note' => 'max']);

        $this->assertTrue($restaurant->fresh()->package_ends_at->isSameDay(now()->addDays(5)));
    }

    public function test_keeps_translations_leaves_a_field_the_form_did_not_send_untouched(): void
    {
        $category = Category::factory()->create([
            'restaurant_id' => $this->owner()->id,
            'name' => ['en' => 'Mains', 'ar' => 'أطباق رئيسية'],
            'description' => ['en' => 'From noon'],
        ]);

        $merged = $this->pageFor($category)->merge(['name' => ['en' => 'Main dishes'], 'display_order' => 3]);

        $this->assertSame(['name' => ['en' => 'Main dishes', 'ar' => 'أطباق رئيسية'], 'display_order' => 3], $merged);
        $this->assertArrayNotHasKey('description', $merged);
        $this->assertSame(['en' => 'From noon'], $category->getTranslations('description'));
    }

    public function test_keeps_translations_forgets_a_language_sent_blank(): void
    {
        $category = Category::factory()->create([
            'restaurant_id' => $this->owner()->id,
            'name' => ['en' => 'Mains', 'fr' => 'Plats'],
        ]);

        $merged = $this->pageFor($category)->merge(['name' => ['en' => 'Mains', 'fr' => '  ']]);
        $category->fill($merged)->save();

        $this->assertSame(['en' => 'Mains'], $category->fresh()->getTranslations('name'));
    }

    public function test_keeps_translations_on_a_new_record_stores_only_filled_languages(): void
    {
        $merged = $this->pageFor(null)->merge(['name' => ['en' => 'Desserts', 'ar' => null], 'description' => ['en' => '']]);

        $this->assertSame(['name' => ['en' => 'Desserts'], 'description' => []], $merged);
    }

    public function test_keeps_translations_fills_every_stored_language(): void
    {
        $category = Category::factory()->create([
            'restaurant_id' => $this->owner()->id,
            'name' => ['en' => 'Mains', 'tr' => 'Ana yemekler'],
        ]);

        $filled = $this->pageFor($category)->fill(['display_order' => 0]);

        $this->assertSame(['en' => 'Mains', 'tr' => 'Ana yemekler'], $filled['name']);
        $this->assertSame([], $filled['description']);
        $this->assertSame(0, $filled['display_order']);
    }

    public function test_keeps_translations_reads_the_fields_from_the_model(): void
    {
        $page = new class
        {
            use KeepsTranslations;

            public function getModel(): string
            {
                return Package::class;
            }

            public function getRecord(): ?Model
            {
                return null;
            }

            /**
             * @param  array<string, mixed>  $data
             * @return array<string, mixed>
             */
            public function merge(array $data): array
            {
                return $this->mergeTranslations($data);
            }
        };

        $this->assertSame(
            ['name' => ['ar' => 'برو'], 'slug' => 'pro'],
            $page->merge(['name' => ['en' => '', 'ar' => 'برو'], 'slug' => 'pro']),
        );
    }

    /**
     * A stand-in for a create (null) or edit page of a category.
     */
    private function pageFor(?Category $record): object
    {
        return new class($record)
        {
            use KeepsTranslations;

            public function __construct(private ?Category $record) {}

            public function getModel(): string
            {
                return Category::class;
            }

            public function getRecord(): ?Category
            {
                return $this->record;
            }

            /**
             * @param  array<string, mixed>  $data
             * @return array<string, mixed>
             */
            public function merge(array $data): array
            {
                return $this->mergeTranslations($data);
            }

            /**
             * @param  array<string, mixed>  $data
             * @return array<string, mixed>
             */
            public function fill(array $data): array
            {
                return $this->fillTranslations($data);
            }
        };
    }
}
