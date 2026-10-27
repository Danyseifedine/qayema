<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Categories\CategoryResource;
use App\Filament\Admin\Resources\Dishes\DishResource;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\CategoriesRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\DishesRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\SocialLinksRelationManager;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Models\Category;
use App\Models\Dish;
use App\Models\RestaurantSocialLink;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A restaurant's admin page is one place for all of it: its details with an
 * "At a glance" summary, then tabs for overrides, history, orders and its
 * menu (dishes, categories, links), each only its own rows.
 */
class RestaurantPageTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_details_and_every_tab_share_one_page(): void
    {
        $restaurant = $this->owner();

        $this->get(RestaurantResource::getUrl('edit', ['record' => $restaurant]))
            ->assertOk()
            ->assertSeeInOrder(['Details', 'Extras &amp; overrides', 'Package history', 'Orders', 'Dishes', 'Categories', 'Social links', 'Offers'], false);
    }

    public function test_at_a_glance_sums_up_the_restaurant(): void
    {
        $restaurant = $this->ownerOn('free', ['is_active' => true]);
        Dish::factory()->count(3)->create(['restaurant_id' => $restaurant->id]);

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertSee('At a glance')
            ->assertSee('Online')
            ->assertSee('3 / 40 dishes')
            ->assertSee('0 views, 0 QR scans, 0 orders');
    }

    public function test_a_switched_off_or_lapsed_menu_says_so(): void
    {
        $off = $this->owner(['is_active' => false]);
        $lapsed = $this->ownerOn('pro', ['is_active' => true, 'package_ends_at' => now()->subDays(10)]);

        Livewire::test(EditRestaurant::class, ['record' => $off->id])->assertSee('Switched off');
        Livewire::test(EditRestaurant::class, ['record' => $lapsed->id])
            ->assertSee('Offline since '.$lapsed->offlineAt()->toFormattedDateString().' (package ended)');
    }

    public function test_the_header_opens_the_menu_and_extends_the_package(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(3)]);

        $page = Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertActionHasUrl('menu', route('public.menu', $restaurant->slug))
            ->assertActionVisible('extendPackage')
            ->callAction('extendPackage', data: ['extend_by' => 1]);

        $end = $restaurant->fresh()->package_ends_at;
        $this->assertTrue($end->isSameDay(now()->addDays(3)->addMonthNoOverflow()));

        // The form shows the new end, so saving it does not put the old one back.
        $page->call('save')->assertHasNoFormErrors();
        $this->assertTrue($restaurant->fresh()->package_ends_at->isSameDay($end));
    }

    public function test_a_package_that_runs_forever_has_nothing_to_extend(): void
    {
        Livewire::test(EditRestaurant::class, ['record' => $this->owner()->id])
            ->assertActionHidden('extendPackage');
    }

    public function test_the_dishes_tab_lists_only_this_restaurants_dishes(): void
    {
        $restaurant = $this->owner();
        $mine = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        $theirs = Dish::factory()->create(['restaurant_id' => $this->owner()->id]);

        Livewire::test(DishesRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs])
            ->assertTableColumnHidden('restaurant.name')
            ->assertActionHasUrl(TestAction::make('edit')->table($mine), DishResource::getUrl('edit', ['record' => $mine]));
    }

    public function test_the_dishes_tab_filters_by_this_restaurants_categories_only(): void
    {
        $restaurant = $this->owner();
        $grills = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $kafta = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $grills->id]);
        $bread = Dish::factory()->create(['restaurant_id' => $restaurant->id]);

        Livewire::test(DishesRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->filterTable('category_id', $grills->id)
            ->assertCanSeeTableRecords([$kafta])
            ->assertCanNotSeeTableRecords([$bread]);
    }

    public function test_the_categories_and_links_tabs_list_only_their_own(): void
    {
        $restaurant = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        $otherCategory = Category::factory()->create(['restaurant_id' => $this->owner()->id]);
        $link = RestaurantSocialLink::factory()->create(['restaurant_id' => $restaurant->id]);
        $otherLink = RestaurantSocialLink::factory()->create(['restaurant_id' => $this->owner()->id]);

        Livewire::test(CategoriesRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->assertCanSeeTableRecords([$category])
            ->assertCanNotSeeTableRecords([$otherCategory])
            ->assertActionHasUrl(TestAction::make('edit')->table($category), CategoryResource::getUrl('edit', ['record' => $category]));

        Livewire::test(SocialLinksRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->assertCanSeeTableRecords([$link])
            ->assertCanNotSeeTableRecords([$otherLink]);
    }
}
