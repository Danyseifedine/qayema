<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\RestaurantSocialLinks\Pages\CreateRestaurantSocialLink;
use App\Filament\Admin\Resources\RestaurantSocialLinks\Pages\EditRestaurantSocialLink;
use App\Filament\Admin\Resources\RestaurantSocialLinks\Pages\ListRestaurantSocialLinks;
use App\Filament\Admin\Resources\RestaurantSocialLinks\RestaurantSocialLinkResource;
use App\Models\RestaurantSocialLink;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The Social Links resource: the list with its labels and filters, the
 * create and edit forms with their validation, and deleting one or many.
 */
class SocialLinkAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_list_labels_every_platform_and_shows_the_restaurant(): void
    {
        $restaurant = $this->owner(['name' => ['en' => 'Zaatar House']]);
        $links = collect(['instagram', 'x', 'facebook', 'tiktok', 'snapchat'])
            ->mapWithKeys(fn (string $platform): array => [$platform => RestaurantSocialLink::factory()->create([
                'restaurant_id' => $restaurant->id,
                'platform' => $platform,
                'url' => "https://{$platform}.example.com/zaatar",
            ])]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurantSocialLinks::class)
            ->assertOk()
            ->assertCanSeeTableRecords($links->values())
            ->assertTableColumnFormattedStateSet('platform', 'Instagram', $links['instagram'])
            ->assertTableColumnFormattedStateSet('platform', 'X (Twitter)', $links['x'])
            ->assertTableColumnFormattedStateSet('platform', 'Facebook', $links['facebook'])
            ->assertTableColumnFormattedStateSet('platform', 'TikTok', $links['tiktok'])
            ->assertTableColumnFormattedStateSet('platform', 'Snapchat', $links['snapchat'])
            ->assertTableColumnStateSet('restaurant.name', 'Zaatar House', $links['x'])
            ->assertTableColumnStateSet('url', 'https://tiktok.example.com/zaatar', $links['tiktok'])
            ->assertSee('X (Twitter)')
            ->assertSee('Snapchat');
    }

    public function test_the_list_filters_by_platform_and_restaurant(): void
    {
        $first = $this->owner();
        $second = $this->owner();
        $instagram = RestaurantSocialLink::factory()->create(['restaurant_id' => $first->id, 'platform' => 'instagram']);
        $facebook = RestaurantSocialLink::factory()->create(['restaurant_id' => $second->id, 'platform' => 'facebook']);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurantSocialLinks::class)
            ->filterTable('platform', 'instagram')
            ->assertCanSeeTableRecords([$instagram])
            ->assertCanNotSeeTableRecords([$facebook])
            ->resetTableFilters()
            ->filterTable('restaurant_id', $second->id)
            ->assertCanSeeTableRecords([$facebook])
            ->assertCanNotSeeTableRecords([$instagram]);
    }

    public function test_the_list_offers_create_and_row_edit(): void
    {
        $link = RestaurantSocialLink::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurantSocialLinks::class)
            ->assertActionHasUrl(TestAction::make('create'), RestaurantSocialLinkResource::getUrl('create'))
            ->assertActionHasUrl(TestAction::make('edit')->table($link), RestaurantSocialLinkResource::getUrl('edit', ['record' => $link]));
    }

    public function test_bulk_delete_removes_only_the_selected_links(): void
    {
        $doomed = RestaurantSocialLink::factory()->count(2)->create();
        $kept = RestaurantSocialLink::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurantSocialLinks::class)
            ->selectTableRecords($doomed->pluck('id')->all())
            ->callAction(TestAction::make('delete')->table()->bulk())
            ->assertHasNoActionErrors();

        foreach ($doomed as $link) {
            $this->assertModelMissing($link);
        }
        $this->assertModelExists($kept);
    }

    public function test_an_admin_can_create_a_link_for_any_restaurant(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(CreateRestaurantSocialLink::class)
            ->fillForm([
                'restaurant_id' => $restaurant->id,
                'platform' => 'tiktok',
                'url' => 'https://tiktok.com/@menu',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('restaurant_social_links', [
            'restaurant_id' => $restaurant->id,
            'platform' => 'tiktok',
            'url' => 'https://tiktok.com/@menu',
        ]);
    }

    public function test_creating_requires_every_field_and_a_real_url(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateRestaurantSocialLink::class)
            ->fillForm(['restaurant_id' => null, 'platform' => null, 'url' => 'not a url'])
            ->call('create')
            ->assertHasFormErrors(['restaurant_id' => 'required', 'platform' => 'required', 'url' => 'url']);

        $this->assertDatabaseCount('restaurant_social_links', 0);
    }

    public function test_creating_refuses_an_unknown_platform_and_an_overlong_url(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(CreateRestaurantSocialLink::class)
            ->fillForm([
                'restaurant_id' => $restaurant->id,
                'platform' => 'myspace',
                'url' => 'https://example.com/'.str_repeat('a', 500),
            ])
            ->call('create')
            ->assertHasFormErrors(['platform', 'url' => 'max']);

        $this->assertDatabaseCount('restaurant_social_links', 0);
    }

    public function test_the_edit_page_loads_the_link_and_saves_changes(): void
    {
        $link = RestaurantSocialLink::factory()->create(['platform' => 'instagram', 'url' => 'https://instagram.com/old']);
        $other = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurantSocialLink::class, ['record' => $link->getRouteKey()])
            ->assertOk()
            ->assertSchemaStateSet([
                'restaurant_id' => $link->restaurant_id,
                'platform' => 'instagram',
                'url' => 'https://instagram.com/old',
            ])
            ->fillForm(['restaurant_id' => $other->id, 'platform' => 'facebook', 'url' => 'https://facebook.com/new'])
            ->call('save')
            ->assertHasNoFormErrors();

        $link->refresh();
        $this->assertSame($other->id, $link->restaurant_id);
        $this->assertSame('facebook', $link->platform);
        $this->assertSame('https://facebook.com/new', $link->url);
    }

    public function test_the_edit_page_rejects_an_invalid_url_and_keeps_the_old_one(): void
    {
        $link = RestaurantSocialLink::factory()->create(['url' => 'https://instagram.com/keep']);
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurantSocialLink::class, ['record' => $link->getRouteKey()])
            ->fillForm(['url' => ''])
            ->call('save')
            ->assertHasFormErrors(['url' => 'required']);

        $this->assertSame('https://instagram.com/keep', $link->fresh()->url);
    }

    public function test_the_edit_page_deletes_the_link(): void
    {
        $link = RestaurantSocialLink::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurantSocialLink::class, ['record' => $link->getRouteKey()])
            ->callAction('delete')
            ->assertRedirect(RestaurantSocialLinkResource::getUrl('index'));

        $this->assertModelMissing($link);
    }

    public function test_the_create_and_edit_pages_render_over_http_for_an_admin_only(): void
    {
        $link = RestaurantSocialLink::factory()->create();
        $this->actingAs($this->admin());

        $this->get(RestaurantSocialLinkResource::getUrl('create'))->assertOk()->assertSee('Profile URL');
        $this->get(RestaurantSocialLinkResource::getUrl('edit', ['record' => $link]))->assertOk()->assertSee('Profile URL')->assertSee('Delete');
    }

    public function test_an_owner_cannot_open_the_edit_page_even_for_their_own_link(): void
    {
        $restaurant = $this->owner();
        $link = RestaurantSocialLink::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($restaurant->user)
            ->get(RestaurantSocialLinkResource::getUrl('edit', ['record' => $link]))
            ->assertForbidden();
    }
}
