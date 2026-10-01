<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Filament\Admin\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Admin\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Admin\Resources\Dishes\Pages\ListDishes;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Packages\Pages\ListPackages;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\PackageChangesRelationManager;
use App\Filament\Admin\Resources\RestaurantSocialLinks\Pages\ListRestaurantSocialLinks;
use App\Filament\Admin\Resources\Templates\Pages\ListTemplates;
use App\Filament\Admin\Resources\Templates\Tables\TemplatesTable;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use App\Models\Template;
use App\Models\User;
use App\Services\Packages\PackageAssigner;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * An admin can delete anything an owner made: a whole user, a menu, a
 * category, a dish, a link, an order, a message, a design, a package, a line
 * of history. Each delete takes its images out of storage too.
 */
class AdminDeleteTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('media-library.disk_name'));
        $this->actingAs($this->admin());
    }

    /** A restaurant with a logo, a category, a dish with a photo, a link and an order. */
    private function fullMenu(): array
    {
        $restaurant = $this->owner();
        $logo = $restaurant->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
        $photo = $dish->addMedia(UploadedFile::fake()->image('dish.png'))->toMediaCollection('image');
        RestaurantSocialLink::create(['restaurant_id' => $restaurant->id, 'platform' => 'instagram', 'url' => 'https://instagram.com/olive']);
        $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);
        $order->items()->create(['name' => 'Kafta', 'unit_price' => '5.00', 'quantity' => 2, 'line_total' => '10.00']);

        return [$restaurant, $logo, $dish, $photo, $order];
    }

    public function test_deleting_a_user_takes_their_menu_and_every_image_with_it(): void
    {
        // The database deleted the restaurant on its own before, leaving the
        // logo and every dish photo in storage for good.
        [$restaurant, $logo, $dish, $photo] = $this->fullMenu();
        $user = $restaurant->user;
        $files = [$logo->getPath(), $photo->getPath()];

        Livewire::test(ListUsers::class)->callAction(TestAction::make('delete')->table($user));

        // Each side deletes the other once, never in a loop.
        $this->assertModelMissing($user);
        $this->assertModelMissing($restaurant);
        $this->assertModelMissing($dish);
        $this->assertSame(0, Media::query()->count());
        foreach ($files as $file) {
            $this->assertFileDoesNotExist($file);
        }
    }

    public function test_deleting_a_restaurant_deletes_its_owners_account_too(): void
    {
        [$restaurant, $logo, $dish, $photo, $order] = $this->fullMenu();
        $owner = $restaurant->user;
        $files = [$logo->getPath(), $photo->getPath()];

        Livewire::test(ListRestaurants::class)->callAction(TestAction::make('delete')->table($restaurant));

        $this->assertModelMissing($restaurant);
        $this->assertModelMissing($owner);
        $this->assertModelMissing($dish);
        $this->assertModelMissing($order);
        $this->assertSame(0, RestaurantSocialLink::query()->count());
        foreach ($files as $file) {
            $this->assertFileDoesNotExist($file);
        }
    }

    public function test_the_edit_page_deletes_the_owner_too_and_says_so(): void
    {
        [$restaurant] = $this->fullMenu();
        $owner = $restaurant->user;

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->getRouteKey()])
            ->assertActionExists('delete', fn ($action): bool => str_contains((string) $action->getModalDescription(), "the owner's account"))
            ->callAction('delete');

        $this->assertModelMissing($restaurant);
        $this->assertModelMissing($owner);
    }

    public function test_an_admins_account_is_never_deleted_with_a_restaurant(): void
    {
        $admin = $this->admin();
        $restaurant = $this->owner(['user_id' => $admin->id]);

        $restaurant->delete();

        $this->assertModelMissing($restaurant);
        $this->assertModelExists($admin);
    }

    public function test_menus_deleted_together_still_take_their_photos(): void
    {
        [$first, , , $photo] = $this->fullMenu();
        [$second] = $this->fullMenu();
        $owners = [$first->user, $second->user];

        Livewire::test(ListRestaurants::class)
            ->selectTableRecords([$first->getKey(), $second->getKey()])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertSame(0, Restaurant::query()->count());
        $this->assertModelMissing($owners[0]);
        $this->assertModelMissing($owners[1]);
        $this->assertSame(0, Media::query()->count());
        $this->assertFileDoesNotExist($photo->getPath());
    }

    public function test_a_dish_is_deleted_from_its_row_with_its_photo(): void
    {
        [, , $dish, $photo] = $this->fullMenu();

        Livewire::test(ListDishes::class)->callAction(TestAction::make('delete')->table($dish));

        $this->assertModelMissing($dish);
        $this->assertFileDoesNotExist($photo->getPath());
    }

    public function test_a_deleted_category_leaves_its_dishes_on_the_menu(): void
    {
        [, , $dish] = $this->fullMenu();

        Livewire::test(ListCategories::class)->callAction(TestAction::make('delete')->table($dish->category));

        $this->assertModelExists($dish);
        $this->assertNull($dish->fresh()->category_id);
    }

    public function test_a_social_link_is_deleted_from_its_row(): void
    {
        [$restaurant] = $this->fullMenu();
        $link = $restaurant->socialLinks()->first();

        Livewire::test(ListRestaurantSocialLinks::class)->callAction(TestAction::make('delete')->table($link));

        $this->assertModelMissing($link);
    }

    public function test_orders_are_listed_on_the_restaurant_and_deleted_there(): void
    {
        [$restaurant, , , , $order] = $this->fullMenu();
        $other = Order::factory()->create(['restaurant_id' => $restaurant->id]);

        $orders = Livewire::test(OrdersRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->assertCanSeeTableRecords([$order, $other])
            ->assertSee('2 × Kafta');

        $orders->callAction(TestAction::make('delete')->table($order));
        $this->assertModelMissing($order);
        $this->assertSame(0, $order->items()->count());

        $orders->selectTableRecords([$other->getKey()])->callAction(TestAction::make('delete')->table()->bulk());
        $this->assertModelMissing($other);
    }

    public function test_a_line_of_package_history_can_be_deleted(): void
    {
        [$restaurant] = $this->fullMenu();
        app(PackageAssigner::class)->assign($restaurant, Package::findBySlug('pro'));
        $line = $restaurant->packageChanges()->latest('id')->first();

        Livewire::test(PackageChangesRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->callAction(TestAction::make('delete')->table($line));

        $this->assertModelMissing($line);
        // The history line goes; the package stays as it is.
        $this->assertSame('pro', $restaurant->fresh()->package->slug);
    }

    public function test_contact_messages_are_deleted_from_the_list_or_the_message(): void
    {
        [$first, $second, $third] = ContactMessage::factory()->count(3)->create();

        Livewire::test(ListContactMessages::class)->callAction(TestAction::make('delete')->table($first));
        $this->assertModelMissing($first);

        Livewire::test(ListContactMessages::class)
            ->selectTableRecords([$second->getKey()])
            ->callAction(TestAction::make('delete')->table()->bulk());
        $this->assertModelMissing($second);

        Livewire::test(ViewContactMessage::class, ['record' => $third->getKey()])->callAction('delete');
        $this->assertModelMissing($third);
    }

    public function test_a_design_says_how_many_menus_use_it_before_it_goes(): void
    {
        $template = Template::factory()->create();
        $this->owner(['template_id' => $template->id]);
        $this->owner(['template_id' => $template->id]);

        $this->assertStringStartsWith('2 restaurants use this design. Their menus go offline', TemplatesTable::deleteWarning($template));
        $this->assertStringStartsWith('No restaurant uses this design', TemplatesTable::deleteWarning(Template::factory()->create()));

        Livewire::test(ListTemplates::class)->callAction(TestAction::make('delete')->table($template));

        $this->assertModelMissing($template);
    }

    public function test_a_deleted_package_moves_its_restaurants_to_the_default_with_a_note(): void
    {
        $pro = Package::findBySlug('pro');
        $restaurant = $this->ownerOn('pro');
        $this->assertStringStartsWith('1 restaurant on this package move to Free', PackageResource::deleteWarning($pro));

        Livewire::test(ListPackages::class)->callAction(TestAction::make('delete')->table($pro));

        $this->assertModelMissing($pro);
        $restaurant->refresh();
        $this->assertTrue($restaurant->package->is_default);
        $this->assertSame('The Pro package was deleted.', $restaurant->packageChanges()->latest('id')->first()->note);
    }

    public function test_the_default_package_can_never_be_deleted(): void
    {
        $default = Package::default();

        Livewire::test(ListPackages::class)->assertActionHidden(TestAction::make('delete')->table($default));
        Livewire::test(EditPackage::class, ['record' => $default->getKey()])->assertActionHidden('delete');

        // Nor around the panel: the model refuses too.
        $this->assertFalse($default->delete());
        $this->assertModelExists($default);
    }

    public function test_an_owner_cannot_reach_any_of_it(): void
    {
        [$restaurant] = $this->fullMenu();
        $this->actingAs($restaurant->user);

        $this->get(route('filament.admin.resources.restaurants.index'))->assertForbidden();
        $this->assertModelExists($restaurant);
        $this->assertInstanceOf(User::class, $restaurant->user);
    }
}
