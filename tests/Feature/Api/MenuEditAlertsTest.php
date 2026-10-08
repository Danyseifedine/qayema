<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\DeviceToken;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\Support\FakesFirebase;
use Tests\TestCase;

/**
 * An owner editing their menu from the dashboard tells the admins' phones
 * (TellAdminsAboutMenuEdits), once an hour at most per restaurant.
 */
class MenuEditAlertsTest extends TestCase
{
    use CreatesOwners, FakesFirebase, RefreshDatabase;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fakeFirebase();
        DeviceToken::factory()->for($this->admin())->create();
        $this->restaurant = $this->owner(['name' => ['en' => 'Beit Rami']]);
        $this->restaurant->user->update(['name' => 'Moudi']);
    }

    private function renameCategory(string $name): \Illuminate\Testing\TestResponse
    {
        $category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);

        return $this->actingAs($this->restaurant->user)
            ->patchJson(route('api.categories.update', $category), ['name' => ['en' => $name]]);
    }

    public function test_an_edit_tells_the_admins_which_restaurant_is_editing(): void
    {
        $this->renameCategory('Grills')->assertOk();

        $this->assertCount(1, $this->pushes);
        $notification = $this->pushes[0]['message']['notification'];
        $this->assertSame('Beit Rami is editing its menu', $notification['title']);
        $this->assertSame('More changes in the next hour stay quiet.', $notification['body']);
        $this->assertStringNotContainsString('Moudi', $notification['title'].$notification['body']);
        $this->assertSame(
            ['type' => 'menu_editing', 'restaurant_id' => (string) $this->restaurant->id],
            $this->pushes[0]['message']['data'],
        );
    }

    public function test_many_edits_in_an_hour_are_one_notification(): void
    {
        $this->renameCategory('Grills')->assertOk();
        $this->renameCategory('Mezze')->assertOk();
        $dish = Dish::factory()->create(['restaurant_id' => $this->restaurant->id]);
        $this->actingAs($this->restaurant->user)
            ->patchJson(route('api.dishes.availability', $dish), ['is_available' => false])
            ->assertOk();

        $this->assertCount(1, $this->pushes);

        $this->travel(61)->minutes();
        $this->renameCategory('Desserts')->assertOk();

        $this->assertCount(2, $this->pushes);
    }

    public function test_each_restaurant_has_its_own_hour(): void
    {
        $this->renameCategory('Grills')->assertOk();

        $other = $this->owner();
        $this->app['auth']->forgetGuards();
        $this->actingAs($other->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'Drinks']])
            ->assertCreated();

        $this->assertCount(2, $this->pushes);
        $this->assertSame((string) $other->id, $this->pushes[1]['message']['data']['restaurant_id']);
    }

    public function test_a_failed_edit_is_not_an_edit_and_leaves_the_hour_open(): void
    {
        $this->renameCategory('')->assertUnprocessable();
        $this->assertSame([], $this->pushes);

        // The refused change did not start the quiet hour: the next one is told.
        $this->renameCategory('Grills')->assertOk();
        $this->assertCount(1, $this->pushes);
    }

    public function test_reading_the_menu_is_not_an_edit(): void
    {
        $this->actingAs($this->restaurant->user)->getJson(route('api.categories.index'))->assertOk();

        $this->assertSame([], $this->pushes);
    }

    public function test_orders_and_the_account_are_not_menu_edits(): void
    {
        $this->restaurant->update(['order_mode' => 'menu']);
        $order = Order::factory()->create(['restaurant_id' => $this->restaurant->id, 'channel' => 'menu']);

        $this->actingAs($this->restaurant->user)
            ->patchJson(route('api.orders.update', $order), ['status' => 'accepted'])
            ->assertOk();
        $this->actingAs($this->restaurant->user)
            ->patchJson(route('api.account.update'), ['name' => 'Moudi H'])
            ->assertOk();

        $this->assertSame([], $this->pushes);
    }

    public function test_an_admin_signed_in_as_the_owner_is_not_the_owner_editing(): void
    {
        $this->actingAs($this->admin());
        $this->get(route('impersonate', $this->restaurant->user_id))->assertRedirect();

        $category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);
        $this->patchJson(route('api.categories.update', $category), ['name' => ['en' => 'Grills']])->assertOk();

        $this->assertSame([], $this->pushes);
    }

    public function test_every_menu_area_counts(): void
    {
        foreach ([
            fn () => $this->patchJson(route('api.restaurant.update'), [
                'name' => ['en' => 'Beit Rami'], 'phone' => '70123456', 'currency' => 'USD',
            ]),
            fn () => $this->putJson(route('api.restaurant.slug'), ['slug' => 'beit-rami-'.uniqid()]),
            fn () => $this->postJson(route('api.social-links.store'), ['platform' => 'instagram', 'url' => 'https://instagram.com/beitrami']),
        ] as $index => $edit) {
            cache()->flush();
            $this->actingAs($this->restaurant->user);
            $edit()->assertSuccessful();

            $this->assertCount($index + 1, $this->pushes, "edit #{$index} did not notify");
        }
    }
}
