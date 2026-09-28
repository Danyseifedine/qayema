<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Categories\CategoryResource;
use App\Filament\Admin\Resources\Categories\Pages\CreateCategory;
use App\Filament\Admin\Resources\Categories\Pages\EditCategory;
use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use App\Models\Dish;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The categories resource: the list with real rows, its filter and bulk
 * delete, and the create and edit forms' validation.
 */
class CategoryAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_list_shows_each_category_with_its_restaurant_and_dish_count(): void
    {
        $restaurant = $this->owner(['name' => ['en' => 'Cedar House']]);
        $mains = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Mains'], 'display_order' => 2]);
        $starters = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Starters'], 'display_order' => 1]);
        Dish::factory()->count(3)->create(['restaurant_id' => $restaurant->id, 'category_id' => $mains->id]);

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords([$starters, $mains], inOrder: true)
            ->assertTableColumnStateSet('dishes_count', 3, $mains)
            ->assertTableColumnStateSet('dishes_count', 0, $starters)
            ->assertTableColumnStateSet('display_order', 2, $mains)
            ->assertSee('Cedar House');
    }

    public function test_the_list_filters_by_restaurant_and_searches_by_name(): void
    {
        $mine = Category::factory()->create(['restaurant_id' => $this->owner()->id, 'name' => ['en' => 'Grills']]);
        $theirs = Category::factory()->create(['restaurant_id' => $this->owner()->id, 'name' => ['en' => 'Salads']]);

        Livewire::test(ListCategories::class)
            ->filterTable('restaurant_id', $mine->restaurant_id)
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);

        Livewire::test(ListCategories::class)
            ->searchTable('Salads')
            ->assertCanSeeTableRecords([$theirs])
            ->assertCanNotSeeTableRecords([$mine]);
    }

    public function test_bulk_delete_removes_only_the_selected_categories(): void
    {
        $restaurant = $this->owner();
        [$first, $second, $kept] = Category::factory()->count(3)->create(['restaurant_id' => $restaurant->id])->all();

        Livewire::test(ListCategories::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertDatabaseMissing('categories', ['id' => $first->id]);
        $this->assertDatabaseMissing('categories', ['id' => $second->id]);
        $this->assertDatabaseHas('categories', ['id' => $kept->id]);
    }

    public function test_the_edit_page_renders_over_http_and_moves_a_category(): void
    {
        $category = Category::factory()->create(['restaurant_id' => $this->owner()->id, 'name' => ['en' => 'Breakfast'], 'display_order' => 0]);
        $other = $this->owner();

        $this->get(CategoryResource::getUrl('edit', ['record' => $category]))->assertOk()->assertSee('Breakfast');

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->assertSchemaStateSet(['restaurant_id' => $category->restaurant_id, 'display_order' => 0])
            ->fillForm(['restaurant_id' => $other->id, 'display_order' => 7])
            ->call('save')
            ->assertHasNoFormErrors();

        $category = $category->fresh();
        $this->assertSame($other->id, $category->restaurant_id);
        $this->assertSame(7, $category->display_order);
    }

    public function test_the_edit_form_refuses_a_blank_name_and_an_overlong_description(): void
    {
        $category = Category::factory()->create(['restaurant_id' => $this->owner()->id, 'name' => ['en' => 'Kept']]);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->fillForm(['name.en' => '', 'description.en' => str_repeat('a', 301)])
            ->call('save')
            ->assertHasFormErrors(['name.en' => 'required', 'description.en' => 'max']);

        $this->assertSame(['en' => 'Kept'], $category->fresh()->getTranslations('name'));
    }

    public function test_clearing_the_description_drops_that_language(): void
    {
        $category = Category::factory()->create([
            'restaurant_id' => $this->owner()->id,
            'description' => ['en' => 'From noon', 'ar' => 'من الظهر'],
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->fillForm(['description.en' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['ar' => 'من الظهر'], $category->fresh()->getTranslations('description'));
    }

    public function test_the_delete_header_action_removes_the_category(): void
    {
        $category = Category::factory()->create(['restaurant_id' => $this->owner()->id]);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_create_requires_a_restaurant_and_a_name(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm(['restaurant_id' => null, 'name.en' => ''])
            ->call('create')
            ->assertHasFormErrors(['restaurant_id' => 'required', 'name.en' => 'required']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_create_rejects_a_restaurant_that_does_not_exist(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm(['restaurant_id' => 999999, 'name.en' => 'Ghost'])
            ->call('create')
            ->assertHasFormErrors(['restaurant_id']);

        $this->assertDatabaseCount('categories', 0);
    }

    public function test_the_create_page_renders_over_http(): void
    {
        $this->get(CategoryResource::getUrl('create'))->assertOk();
    }
}
