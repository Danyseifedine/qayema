<?php

namespace Tests\Feature\MenuOwner;

use App\Jobs\ProcessMenuScan;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuScan;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MenuScanControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Restaurant}
     */
    private function menuOwnerWithRestaurant(): array
    {
        $user = User::factory()->create(['onboarding_completed_at' => now()]);
        $restaurant = Restaurant::factory()->create(['user_id' => $user->id]);

        return [$user, $restaurant];
    }

    public function test_scan_is_accepted_and_dispatches_processing_job(): void
    {
        Storage::fake('local');
        Queue::fake();
        [$user] = $this->menuOwnerWithRestaurant();

        $response = $this->actingAs($user)->post(route('menu-owner.menu-scan.scan'), [
            'image' => UploadedFile::fake()->image('menu.jpg'),
        ]);

        $response->assertStatus(202);
        Queue::assertPushed(ProcessMenuScan::class);
    }

    public function test_scan_is_blocked_once_daily_limit_is_reached(): void
    {
        Storage::fake('local');
        Queue::fake();
        [$user, $restaurant] = $this->menuOwnerWithRestaurant();

        MenuScan::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);

        $response = $this->actingAs($user)->post(route('menu-owner.menu-scan.scan'), [
            'image' => UploadedFile::fake()->image('menu.jpg'),
        ]);

        $response->assertStatus(429);
        Queue::assertNothingPushed();
    }

    public function test_import_creates_categories_and_dishes_from_a_completed_scan(): void
    {
        [$user, $restaurant] = $this->menuOwnerWithRestaurant();
        $scan = MenuScan::factory()->completed()->create(['restaurant_id' => $restaurant->id]);

        $response = $this->actingAs($user)->postJson(
            route('menu-owner.menu-scan.import', $scan->id),
            ['categories' => [
                ['name' => 'Mains', 'dishes' => [
                    ['name' => 'Kafta', 'price' => 12.5],
                    ['name' => 'Shish Tawook', 'price' => 11],
                ]],
            ]]
        );

        $response->assertOk();
        $this->assertDatabaseHas('categories', ['restaurant_id' => $restaurant->id, 'name' => 'Mains']);
        $this->assertDatabaseHas('dishes', ['restaurant_id' => $restaurant->id, 'name' => 'Kafta']);
        $this->assertSame(2, Dish::where('restaurant_id', $restaurant->id)->count());
        $this->assertSame(1, Category::where('restaurant_id', $restaurant->id)->count());
    }

    public function test_import_is_idempotent_and_rejects_a_second_import(): void
    {
        [$user, $restaurant] = $this->menuOwnerWithRestaurant();
        $scan = MenuScan::factory()->completed()->create(['restaurant_id' => $restaurant->id]);

        $payload = ['categories' => [
            ['name' => 'Mains', 'dishes' => [['name' => 'Kafta', 'price' => 12.5]]],
        ]];

        $this->actingAs($user)->postJson(route('menu-owner.menu-scan.import', $scan->id), $payload)->assertOk();

        $second = $this->actingAs($user)->postJson(route('menu-owner.menu-scan.import', $scan->id), $payload);

        $second->assertStatus(422);
        $this->assertSame(1, Category::where('restaurant_id', $restaurant->id)->count());
    }

    public function test_owner_cannot_access_another_owners_scan_status(): void
    {
        [$ownerA, $restaurantA] = $this->menuOwnerWithRestaurant();
        $scan = MenuScan::factory()->completed()->create(['restaurant_id' => $restaurantA->id]);

        [$ownerB] = $this->menuOwnerWithRestaurant();

        $this->actingAs($ownerB)
            ->getJson(route('menu-owner.menu-scan.status', $scan->id))
            ->assertNotFound();
    }

    public function test_owner_cannot_import_another_owners_scan(): void
    {
        [$ownerA, $restaurantA] = $this->menuOwnerWithRestaurant();
        $scan = MenuScan::factory()->completed()->create(['restaurant_id' => $restaurantA->id]);

        [$ownerB] = $this->menuOwnerWithRestaurant();

        $this->actingAs($ownerB)
            ->postJson(route('menu-owner.menu-scan.import', $scan->id), ['categories' => [
                ['name' => 'X', 'dishes' => []],
            ]])
            ->assertNotFound();
    }
}
