<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Admin\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Models\ContactMessage;
use App\Models\Package;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The Contact Messages inbox: read-only, lists plain messages next to package
 * requests, and only offers "Apply this package" when there is a restaurant
 * to apply it to.
 */
class ContactMessageAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_messages_cannot_be_created_from_the_panel(): void
    {
        $this->actingAs($this->admin());

        $this->assertFalse(ContactMessageResource::canCreate());
        $this->assertFalse(ContactMessageResource::hasPage('create'));

        Livewire::test(ListContactMessages::class)
            ->assertActionDoesNotExist('create');
    }

    public function test_the_list_shows_plain_messages_and_package_requests(): void
    {
        $plain = ContactMessage::factory()->create(['name' => 'Walk In', 'message' => 'Do you deliver to Jounieh?']);
        $request = ContactMessage::factory()->packageRequest(Package::findBySlug('premium'))->create(['name' => 'Owner Asking']);
        $this->actingAs($this->admin());

        Livewire::test(ListContactMessages::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$request, $plain], inOrder: false)
            ->assertTableColumnStateSet('package.name', null, $plain)
            ->assertTableColumnStateSet('name', 'Walk In', $plain)
            ->assertTableColumnStateSet('message', 'Do you deliver to Jounieh?', $plain)
            ->assertSee('Premium')
            ->assertSee('Do you deliver to Jounieh?')
            ->assertActionHasUrl(TestAction::make('view')->table($plain), ContactMessageResource::getUrl('view', ['record' => $plain]));
    }

    public function test_the_list_is_newest_first_and_filters_by_requested_package(): void
    {
        $older = ContactMessage::factory()->create(['created_at' => now()->subDays(2)]);
        $pro = ContactMessage::factory()->packageRequest()->create(['created_at' => now()->subDay()]);
        $premium = ContactMessage::factory()->packageRequest(Package::findBySlug('premium'))->create(['created_at' => now()]);
        $this->actingAs($this->admin());

        Livewire::test(ListContactMessages::class)
            ->assertCanSeeTableRecords([$premium, $pro, $older], inOrder: true)
            ->filterTable('package_id', Package::findBySlug('pro')->id)
            ->assertCanSeeTableRecords([$pro])
            ->assertCanNotSeeTableRecords([$premium, $older]);
    }

    public function test_the_list_searches_the_sender_and_the_message(): void
    {
        $hit = ContactMessage::factory()->create(['email' => 'nadine@example.com', 'message' => 'Hello there']);
        $miss = ContactMessage::factory()->create(['email' => 'karim@example.com', 'message' => 'Question about QR codes']);
        $this->actingAs($this->admin());

        Livewire::test(ListContactMessages::class)
            ->searchTable('nadine@')
            ->assertCanSeeTableRecords([$hit])
            ->assertCanNotSeeTableRecords([$miss])
            ->searchTable('QR codes')
            ->assertCanSeeTableRecords([$miss])
            ->assertCanNotSeeTableRecords([$hit]);
    }

    public function test_viewing_a_plain_message_from_a_guest(): void
    {
        $message = ContactMessage::factory()->create([
            'name' => 'Guest Person',
            'email' => 'guest@example.com',
            'ip_address' => '203.0.113.9',
            'message' => 'Is there a free plan?',
        ]);
        $this->actingAs($this->admin());

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->assertOk()
            ->assertSee('Guest Person')
            ->assertSee('mailto:guest@example.com', false)
            ->assertSee('203.0.113.9')
            ->assertSee('Not signed in')
            ->assertSee('Not a package request')
            ->assertSee('Is there a free plan?')
            ->assertActionHidden('applyPackage');
    }

    public function test_viewing_a_package_request_links_the_owners_restaurant_and_offers_to_apply_it(): void
    {
        $restaurant = $this->owner();
        $message = ContactMessage::factory()->packageRequest()->create(['user_id' => $restaurant->user_id]);
        $this->actingAs($this->admin());

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->assertOk()
            ->assertSee($restaurant->user->name)
            ->assertSee(RestaurantResource::getUrl('edit', ['record' => $restaurant]), false)
            ->assertSee('Pro')
            ->assertDontSee('Not a package request')
            ->assertDontSee('Not signed in')
            ->assertActionVisible('applyPackage')
            ->assertActionHasLabel('applyPackage', 'Apply this package');
    }

    public function test_a_package_request_from_an_owner_without_a_restaurant_has_no_link_and_nothing_to_apply(): void
    {
        $user = $this->userWithoutRestaurant();
        $message = ContactMessage::factory()->packageRequest()->create(['user_id' => $user->id]);
        $this->actingAs($this->admin());

        Livewire::test(ViewContactMessage::class, ['record' => $message->getRouteKey()])
            ->assertOk()
            ->assertSee($user->name)
            ->assertDontSee('/admin/restaurants/', false)
            ->assertActionHidden('applyPackage');
    }

    public function test_the_view_page_renders_over_http_for_an_admin(): void
    {
        $message = ContactMessage::factory()->create(['message' => 'Over the wire']);

        $this->actingAs($this->admin())
            ->get(ContactMessageResource::getUrl('view', ['record' => $message]))
            ->assertOk()
            ->assertSee('Over the wire');
    }

    public function test_an_owner_cannot_read_a_message_even_their_own(): void
    {
        $restaurant = $this->owner();
        $message = ContactMessage::factory()->packageRequest()->create(['user_id' => $restaurant->user_id]);

        $this->actingAs($restaurant->user)
            ->get(ContactMessageResource::getUrl('view', ['record' => $message]))
            ->assertForbidden();
    }
}
