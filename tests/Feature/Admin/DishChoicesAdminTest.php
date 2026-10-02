<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Dishes\Pages\EditDish;
use App\Filament\Admin\Resources\Dishes\Pages\ListDishes;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\OrdersRelationManager;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Variants and add-ons in the admin: edited on the dish, counted in the
 * list, and named on the restaurant's orders.
 */
class DishChoicesAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_dish_form_saves_variants_with_their_options_and_addons(): void
    {
        $dish = Dish::factory()->create(['restaurant_id' => $this->owner()->id, 'price' => 8]);
        $undo = Repeater::fake();

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->fillForm([
                'variants' => [[
                    'name' => ['en' => 'Size'],
                    'options' => [
                        ['name' => ['en' => 'Small'], 'price' => 0],
                        ['name' => ['en' => 'Large'], 'price' => 2.5],
                    ],
                ]],
                'addons' => [['name' => ['en' => 'Extra cheese'], 'price' => 1]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $undo();

        $variant = $dish->variants()->firstOrFail();
        $this->assertSame('Size', $variant->getTranslation('name', 'en'));
        $this->assertSame(['Small', 'Large'], $variant->options->map(fn ($option) => $option->getTranslation('name', 'en'))->all());
        $this->assertSame('2.50', (string) $variant->options[1]->price);
        $this->assertSame('Extra cheese', $dish->addons()->firstOrFail()->getTranslation('name', 'en'));
    }

    public function test_an_edit_keeps_a_language_the_form_does_not_show(): void
    {
        $dish = Dish::factory()->withAddons(['Cheese' => 1])->create(['restaurant_id' => $this->owner()->id]);
        $addon = $dish->addons()->firstOrFail();
        $addon->setTranslation('name', 'ar', 'جبنة')->save();

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->assertFormSet(['addons.record-'.$addon->id.'.name.en' => 'Cheese'])
            ->fillForm(['addons.record-'.$addon->id.'.name.en' => 'Extra cheese'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['en' => 'Extra cheese', 'ar' => 'جبنة'], $addon->fresh()->getTranslations('name'));
    }

    public function test_a_variant_needs_two_options(): void
    {
        $dish = Dish::factory()->create(['restaurant_id' => $this->owner()->id]);
        $undo = Repeater::fake();

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->fillForm(['variants' => [['name' => ['en' => 'Size'], 'options' => [['name' => ['en' => 'Only'], 'price' => 0]]]]])
            ->call('save')
            ->assertHasFormErrors(['variants.0.options']);

        $undo();

        $this->assertSame(0, $dish->variants()->count());
    }

    public function test_the_list_counts_each_dishs_variants_and_addons(): void
    {
        $dish = Dish::factory()->withVariants()->withAddons()->create(['restaurant_id' => $this->owner()->id]);

        Livewire::test(ListDishes::class)->assertTableColumnStateSet('choices', '1 / 2', $dish);
    }

    public function test_a_restaurants_orders_name_the_guests_choices(): void
    {
        $restaurant = $this->owner();
        $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);
        OrderItem::factory()->for($order)->create([
            'name' => 'Burger',
            'quantity' => 2,
            'options' => ['variants' => [['name' => 'Size', 'choice' => 'Large', 'price' => '3.00']], 'addons' => [['name' => 'Bacon', 'price' => '2.50']]],
        ]);
        OrderItem::factory()->for($order)->create(['name' => 'Fries', 'quantity' => 1]);

        Livewire::test(OrdersRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->assertSee('2 × Burger (Size: Large, + Bacon); 1 × Fries');
    }
}
