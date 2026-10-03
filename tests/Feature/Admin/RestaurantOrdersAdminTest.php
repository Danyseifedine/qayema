<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\OrdersRelationManager;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A restaurant's orders in the admin say how each came in, and for one
 * placed in the menu, how to reach the guest.
 */
class RestaurantOrdersAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_each_order_says_how_it_came_in(): void
    {
        $this->actingAs($this->admin());
        $restaurant = $this->owner();
        $whatsapp = Order::factory()->create(['restaurant_id' => $restaurant->id]);
        $menu = Order::factory()->inMenu()->create(['restaurant_id' => $restaurant->id]);

        Livewire::test(OrdersRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->assertTableColumnFormattedStateSet('channel', 'Sent to WhatsApp', $whatsapp)
            ->assertTableColumnFormattedStateSet('channel', 'In the menu', $menu)
            ->assertTableColumnFormattedStateSet('fulfilment', 'Delivery', $menu)
            ->assertTableColumnStateSet('guest_name', 'Rami', $menu)
            ->assertSee('+96170123456')
            ->filterTable('channel', 'menu')
            ->assertCanSeeTableRecords([$menu])
            ->assertCanNotSeeTableRecords([$whatsapp]);
    }
}
