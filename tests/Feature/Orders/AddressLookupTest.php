<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * "Use my current location" in the cart: the guest's position comes back as
 * an address line for the address box, asked of OpenStreetMap by the server.
 */
class AddressLookupTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::MultipleLanguages);
    }

    private function shop(array $attributes = []): Restaurant
    {
        return $this->published(array_merge(['slug' => 'olive', 'order_mode' => 'menu', 'second_locale' => 'ar'], $attributes));
    }

    private function fakeNominatim(array $address = [
        'house_number' => '12',
        'road' => 'Bliss Street',
        'suburb' => 'Ras Beirut',
        'city' => 'Beirut',
        'state' => 'Beirut Governorate',
        'postcode' => '1107 2020',
        'country' => 'Lebanon',
    ]): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response(['address' => $address])]);
    }

    public function test_the_street_area_and_town_come_back(): void
    {
        $this->fakeNominatim();
        $shop = $this->shop();

        $this->getJson(route('public.address', [$shop->slug, 'lat' => 33.8959, 'lng' => 35.4784, 'locale' => 'ar']))
            ->assertOk()
            ->assertExactJson(['data' => ['address' => '12 Bliss Street, Ras Beirut, Beirut']]);

        // In the guest's language, from the server, saying who is asking.
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), 'lat=33.8959')
            && $request->hasHeader('Accept-Language', 'ar,en')
            && $request->hasHeader('User-Agent'));
    }

    public function test_the_same_spot_is_asked_once(): void
    {
        $this->fakeNominatim();
        $shop = $this->shop();

        $this->getJson(route('public.address', [$shop->slug, 'lat' => 33.89591, 'lng' => 35.47841]))->assertOk();
        $this->getJson(route('public.address', [$shop->slug, 'lat' => 33.89592, 'lng' => 35.47842]))->assertOk();

        Http::assertSentCount(1);
    }

    public function test_nothing_useful_is_null_and_the_guest_types(): void
    {
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 500)]);
        $shop = $this->shop();

        $this->getJson(route('public.address', [$shop->slug, 'lat' => 1, 'lng' => 1]))
            ->assertOk()
            ->assertExactJson(['data' => ['address' => null]]);
    }

    public function test_only_a_menu_that_takes_orders_in_the_menu(): void
    {
        $this->fakeNominatim();
        $shop = $this->shop(['order_mode' => 'whatsapp']);

        $this->getJson(route('public.address', [$shop->slug, 'lat' => 1, 'lng' => 1]))->assertNotFound();
        Http::assertNothingSent();
    }

    public function test_a_position_has_to_be_on_the_map(): void
    {
        $shop = $this->shop();

        $this->getJson(route('public.address', [$shop->slug, 'lat' => 91, 'lng' => 1]))
            ->assertJsonValidationErrors(['lat']);
    }
}
