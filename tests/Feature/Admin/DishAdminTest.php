<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Dishes\DishResource;
use App\Filament\Admin\Resources\Dishes\Pages\CreateDish;
use App\Filament\Admin\Resources\Dishes\Pages\EditDish;
use App\Filament\Admin\Resources\Dishes\Pages\ListDishes;
use App\Models\Category;
use App\Models\Dish;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The dishes resource: the list with real rows, the availability toggle,
 * filters, bulk delete, and the create and edit forms' validation.
 */
class DishAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_list_shows_each_dish_with_its_restaurant_category_and_price(): void
    {
        $restaurant = $this->owner(['name' => ['en' => 'Cedar House']]);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Grills']]);
        $kafta = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => ['en' => 'Kafta'], 'price' => 12.5, 'display_order' => 2]);
        $loose = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Bread'], 'price' => null, 'display_order' => 1]);

        Livewire::test(ListDishes::class)
            ->assertCanSeeTableRecords([$loose, $kafta], inOrder: true)
            ->assertTableColumnFormattedStateSet('price', '$12.50', $kafta)
            ->assertSee('Cedar House')
            ->assertSee('Grills');
    }

    public function test_the_availability_toggle_in_the_table_hides_and_shows_a_dish(): void
    {
        $dish = Dish::factory()->create(['restaurant_id' => $this->owner()->id]);

        Livewire::test(ListDishes::class)
            ->assertTableColumnStateSet('is_available', true, $dish)
            ->call('updateTableColumnState', 'is_available', (string) $dish->getKey(), false);

        $this->assertFalse($dish->fresh()->is_available);

        Livewire::test(ListDishes::class)
            ->call('updateTableColumnState', 'is_available', (string) $dish->getKey(), true);

        $this->assertTrue($dish->fresh()->is_available);
    }

    public function test_the_filters_narrow_by_restaurant_category_and_availability(): void
    {
        $restaurant = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $inCategory = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
        $hidden = Dish::factory()->unavailable()->create(['restaurant_id' => $restaurant->id]);
        $elsewhere = Dish::factory()->create(['restaurant_id' => $this->owner()->id]);

        Livewire::test(ListDishes::class)
            ->filterTable('restaurant_id', $restaurant->id)
            ->assertCanSeeTableRecords([$inCategory, $hidden])
            ->assertCanNotSeeTableRecords([$elsewhere]);

        Livewire::test(ListDishes::class)
            ->filterTable('category_id', $category->id)
            ->assertCanSeeTableRecords([$inCategory])
            ->assertCanNotSeeTableRecords([$hidden, $elsewhere]);

        Livewire::test(ListDishes::class)
            ->filterTable('is_available', 0)
            ->assertCanSeeTableRecords([$hidden])
            ->assertCanNotSeeTableRecords([$inCategory, $elsewhere]);

        Livewire::test(ListDishes::class)
            ->filterTable('is_available', 1)
            ->assertCanSeeTableRecords([$inCategory, $elsewhere])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_bulk_delete_removes_only_the_selected_dishes(): void
    {
        $restaurant = $this->owner();
        [$first, $second, $kept] = Dish::factory()->count(3)->create(['restaurant_id' => $restaurant->id])->all();

        Livewire::test(ListDishes::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertDatabaseMissing('dishes', ['id' => $first->id]);
        $this->assertDatabaseMissing('dishes', ['id' => $second->id]);
        $this->assertDatabaseHas('dishes', ['id' => $kept->id]);
    }

    public function test_the_edit_page_renders_over_http_and_saves_price_order_and_availability(): void
    {
        $dish = Dish::factory()->create(['restaurant_id' => $this->owner()->id, 'name' => ['en' => 'Hummus'], 'price' => 4, 'is_available' => true]);

        $this->get(DishResource::getUrl('edit', ['record' => $dish]))->assertOk()->assertSee('Hummus');

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->fillForm(['price' => 6.75, 'display_order' => 3, 'is_available' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $dish = $dish->fresh();
        $this->assertEquals(6.75, $dish->price);
        $this->assertSame(3, $dish->display_order);
        $this->assertFalse($dish->is_available);
    }

    public function test_the_edit_form_refuses_a_negative_price_and_a_blank_name(): void
    {
        $dish = Dish::factory()->create(['restaurant_id' => $this->owner()->id, 'name' => ['en' => 'Kept'], 'price' => 4]);

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->fillForm(['name.en' => '', 'price' => -1])
            ->call('save')
            ->assertHasFormErrors(['name.en' => 'required', 'price' => 'min']);

        $this->assertSame('Kept', $dish->fresh()->getTranslation('name', 'en'));
        $this->assertEquals(4, $dish->fresh()->price);
    }

    public function test_an_empty_price_saves_as_no_price(): void
    {
        $dish = Dish::factory()->create(['restaurant_id' => $this->owner()->id, 'price' => 4]);

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->fillForm(['price' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($dish->fresh()->price);
    }

    public function test_clearing_the_ingredients_drops_that_language(): void
    {
        $dish = Dish::factory()->create([
            'restaurant_id' => $this->owner()->id,
            'ingredients' => ['en' => 'Chickpeas', 'ar' => 'حمص حب'],
        ]);

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->fillForm(['ingredients.en' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['ar' => 'حمص حب'], $dish->fresh()->getTranslations('ingredients'));
    }

    public function test_changing_the_restaurant_clears_the_category(): void
    {
        $restaurant = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->assertSchemaStateSet(['category_id' => $category->id])
            ->fillForm(['restaurant_id' => $this->owner()->id])
            ->assertSchemaStateSet(['category_id' => null]);
    }

    public function test_the_delete_header_action_removes_the_dish(): void
    {
        $dish = Dish::factory()->create(['restaurant_id' => $this->owner()->id]);

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('dishes', ['id' => $dish->id]);
    }

    public function test_create_requires_a_restaurant_a_name_and_an_order(): void
    {
        Livewire::test(CreateDish::class)
            ->fillForm(['restaurant_id' => null, 'name.en' => '', 'display_order' => null])
            ->call('create')
            ->assertHasFormErrors(['restaurant_id' => 'required', 'name.en' => 'required', 'display_order' => 'required']);

        $this->assertDatabaseCount('dishes', 0);
    }

    public function test_create_refuses_a_category_of_another_restaurant(): void
    {
        $restaurant = $this->owner();
        $foreign = Category::factory()->create(['restaurant_id' => $this->owner()->id]);

        Livewire::test(CreateDish::class)
            ->fillForm(['restaurant_id' => $restaurant->id, 'category_id' => $foreign->id, 'name.en' => 'Stray', 'price' => 3, 'display_order' => 0])
            ->call('create')
            ->assertHasFormErrors(['category_id']);

        $this->assertDatabaseCount('dishes', 0);
    }

    public function test_the_create_page_renders_over_http(): void
    {
        $this->get(DishResource::getUrl('create'))->assertOk();
    }
}
