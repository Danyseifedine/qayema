<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Menu\MenuToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Services\Menu\MenuToken and VerifyMenuToken: what the menu's own
 * requests carry in place of a CSRF token, since a guest has no session.
 */
class MenuTokenTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Dish $dish;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageSets(Feature::Ordering, 1);
        $this->shop = $this->published(['slug' => 'olive', 'currency' => 'USD']);
        $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
        $this->dish = Dish::factory()->create(['restaurant_id' => $this->shop->id, 'category_id' => $category->id, 'price' => '4.00']);
    }

    private function order(?string $token): TestResponse
    {
        return $this->withHeaders($token === null ? [] : [MenuToken::HEADER => $token])
            ->postJson(route('public.order', $this->shop->slug), ['items' => [['dish_id' => $this->dish->id, 'quantity' => 1]]]);
    }

    public function test_a_token_from_the_page_places_the_order(): void
    {
        $this->order(MenuToken::issue())->assertCreated();

        $this->assertSame(1, $this->shop->orders()->count());
    }

    /** @return array<string, array{0: string|null}> */
    public static function refused(): array
    {
        return [
            'none' => [null],
            'empty' => [''],
            'not a token' => ['hello'],
            'a forged signature' => [time().'.'.str_repeat('a', 64)],
            'a signature in capitals' => [time().'.'.str_repeat('A', 64)],
            'no time' => ['.'.str_repeat('a', 64)],
        ];
    }

    #[DataProvider('refused')]
    public function test_an_order_without_a_real_token_is_refused_and_nothing_is_stored(?string $token): void
    {
        $this->order($token)
            ->assertStatus(419)
            ->assertJsonPath('code', 'menu_expired')
            ->assertJsonPath('message', 'This page was open for too long. Refresh it and try again.');

        $this->assertSame(0, $this->shop->orders()->count());
    }

    public function test_a_page_open_a_day_still_orders_and_one_open_longer_does_not(): void
    {
        $token = MenuToken::issue();

        $this->travel(MenuToken::LIFETIME)->seconds();
        $this->order($token)->assertCreated();

        $this->travel(1)->seconds();
        $this->order($token)->assertStatus(419);
    }

    public function test_a_clock_a_little_fast_is_forgiven_but_not_a_made_up_future(): void
    {
        $this->travel(30)->seconds();
        $soon = MenuToken::issue();
        $this->travel(2)->hours();
        $later = MenuToken::issue();
        $this->travelBack();

        $this->order($soon)->assertCreated();
        $this->order($later)->assertStatus(419);
    }

    public function test_a_clock_up_to_a_minute_fast_is_the_limit(): void
    {
        $this->travel(60)->seconds();
        $atTheLimit = MenuToken::issue();
        $this->travel(1)->seconds();
        $pastIt = MenuToken::issue();
        $this->travelBack();

        $this->assertTrue(MenuToken::isValid($atTheLimit));
        $this->assertFalse(MenuToken::isValid($pastIt));
    }

    public function test_one_token_serves_every_menu_on_the_site(): void
    {
        // It proves a menu page was opened lately, not which one: a guest
        // with two menus open orders from either.
        $other = $this->published(['slug' => 'cedar']);
        $dish = Dish::factory()->create(['restaurant_id' => $other->id, 'category_id' => Category::factory()->create(['restaurant_id' => $other->id])->id, 'price' => '3.00']);

        $this->withHeader(MenuToken::HEADER, MenuToken::issue())
            ->postJson(route('public.order', 'cedar'), ['items' => [['dish_id' => $dish->id, 'quantity' => 1]]])
            ->assertCreated();
    }

    public function test_a_token_signed_with_another_key_is_refused(): void
    {
        $token = MenuToken::issue();
        config(['app.key' => 'base64:'.base64_encode(random_bytes(32))]);

        $this->order($token)->assertStatus(419);
    }

    public function test_the_tracking_needs_it_too(): void
    {
        $this->postJson(route('public.events', $this->shop->slug), ['events' => [['type' => 'map']]])->assertStatus(419);
        $this->withHeader(MenuToken::HEADER, MenuToken::issue())
            ->postJson(route('public.events', $this->shop->slug), ['events' => [['type' => 'map']]])
            ->assertNoContent();

        $this->assertDatabaseCount('menu_events', 1);
    }

    public function test_the_change_to_an_order_needs_it_too(): void
    {
        // Checked before the order is even looked up.
        $this->putJson(route('public.order.update', [$this->shop->slug, str_repeat('a', 40)]), ['items' => [['dish_id' => $this->dish->id, 'quantity' => 2]]])
            ->assertStatus(419);
    }

    public function test_the_menu_stores_no_session_and_no_csrf_cookie_for_a_guest_ordering(): void
    {
        $this->order(MenuToken::issue())
            ->assertCreated()
            ->assertCookieMissing((string) config('session.cookie'))
            ->assertCookieMissing('XSRF-TOKEN');
    }
}
