<?php

namespace Tests\Feature\Admin;

use App\Enums\OfferPlacement;
use App\Enums\OfferRepeat;
use App\Filament\Admin\Resources\Offers\OfferResource;
use App\Filament\Admin\Resources\Offers\Pages\ListOffers;
use App\Filament\Admin\Resources\Offers\Tables\OffersTable;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\OffersRelationManager;
use App\Models\Offer;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Offers in the admin: every restaurant's in one list under Menu content
 * and on each restaurant's page, to see what runs, pause one or delete it.
 * Owners write them; the admin never creates one.
 */
class OfferAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_list_shows_each_offer_with_its_schedule_and_status(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Beirut'));
        $restaurant = $this->ownerOn('premium', ['timezone' => 'Asia/Beirut', 'name' => ['en' => 'Olive House']]);
        $live = Offer::factory()->in(OfferPlacement::Banner, OfferPlacement::Popup)->percentOff(20)->create(['restaurant_id' => $restaurant->id, 'title' => ['en' => 'Lunch deal']]);
        $later = Offer::factory()->create([
            'restaurant_id' => $restaurant->id,
            'repeat' => OfferRepeat::Weekly,
            'weekdays' => ['fri', 'sat'],
            'time_windows' => [['from' => '18:00', 'to' => '22:00']],
        ]);
        $paused = Offer::factory()->paused()->create(['restaurant_id' => $restaurant->id]);

        Livewire::test(ListOffers::class)
            ->assertCanSeeTableRecords([$live, $later, $paused])
            ->assertTableColumnStateSet('status', 'live', $live)
            ->assertTableColumnStateSet('status', 'scheduled', $later)
            ->assertTableColumnStateSet('status', 'paused', $paused)
            ->assertTableColumnStateSet('placements', ['Banner', 'Pop-up'], $live)
            ->assertTableColumnStateSet('discount', '-20% on 0 dishes', $live)
            ->assertSee('Olive House');

        $this->assertSame('Fri, Sat · 18:00 to 22:00', OffersTable::schedule($later));
        $this->assertFalse(OfferResource::canCreate());
    }

    public function test_an_offer_is_paused_and_deleted_from_its_row(): void
    {
        $offer = Offer::factory()->create(['restaurant_id' => $this->ownerOn('premium')->id]);

        Livewire::test(ListOffers::class)
            ->call('updateTableColumnState', 'is_active', (string) $offer->getKey(), false);
        $this->assertFalse($offer->fresh()->is_active);

        Livewire::test(ListOffers::class)->callAction(TestAction::make('delete')->table($offer));
        $this->assertModelMissing($offer);
    }

    public function test_the_restaurants_page_lists_only_its_offers(): void
    {
        $restaurant = $this->ownerOn('premium');
        $mine = Offer::factory()->create(['restaurant_id' => $restaurant->id]);
        $theirs = Offer::factory()->create(['restaurant_id' => $this->ownerOn('premium')->id]);

        Livewire::test(OffersRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs])
            ->assertTableColumnHidden('restaurant.name');
    }
}
