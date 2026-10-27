<?php

namespace Tests\Feature\Requests;

use App\Models\Category;
use App\Models\User;
use App\Support\MenuTextLimits;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Every piece of menu text is taken up to its limit (MenuTextLimits) and
 * refused one character past it, wherever it is written: the owner's API,
 * onboarding and the admin phone app's API.
 */
class MenuTextLimitsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /**
     * $send with a text of $limit characters is taken; one more is a 422
     * on $field.
     *
     * @param  Closure(string): TestResponse  $send
     */
    private function assertLimit(int $limit, string $field, Closure $send): void
    {
        $send(str_repeat('a', $limit))->assertSuccessful();
        $send(str_repeat('a', $limit + 1))->assertUnprocessable()->assertJsonValidationErrors([$field]);
    }

    public function test_a_dish(): void
    {
        $owner = $this->ownerOn('pro');
        $category = Category::factory()->for($owner)->create();
        $dish = fn (Closure $fields): Closure => fn (string $text): TestResponse => $this->actingAs($owner->user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'Kebab'], 'price' => 10, 'category_id' => $category->id, ...$fields($text)]);
        $variant = fn (string $name, string $option): array => ['variants' => [[
            'name' => ['en' => $name],
            'options' => [['name' => ['en' => $option], 'price' => 0], ['name' => ['en' => 'Large'], 'price' => 2]],
        ]]];

        $this->assertLimit(MenuTextLimits::DISH_NAME, 'name.en', $dish(fn (string $text): array => ['name' => ['en' => $text]]));
        $this->assertLimit(MenuTextLimits::INGREDIENTS, 'ingredients.en', $dish(fn (string $text): array => ['ingredients' => ['en' => $text]]));
        $this->assertLimit(MenuTextLimits::CHOICE_NAME, 'variants.0.name.en', $dish(fn (string $text): array => $variant($text, 'Small')));
        $this->assertLimit(MenuTextLimits::CHOICE_NAME, 'variants.0.options.0.name.en', $dish(fn (string $text): array => $variant('Size', $text)));
        $this->assertLimit(MenuTextLimits::CHOICE_NAME, 'addons.0.name.en', $dish(fn (string $text): array => ['addons' => [['name' => ['en' => $text], 'price' => 1]]]));
    }

    public function test_a_category(): void
    {
        $owner = $this->owner();
        $category = fn (Closure $fields): Closure => fn (string $text): TestResponse => $this->actingAs($owner->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'Grills'], ...$fields($text)]);

        $this->assertLimit(MenuTextLimits::CATEGORY_NAME, 'name.en', $category(fn (string $text): array => ['name' => ['en' => $text]]));
        $this->assertLimit(MenuTextLimits::CATEGORY_DESCRIPTION, 'description.en', $category(fn (string $text): array => ['description' => ['en' => $text]]));
    }

    public function test_the_restaurant(): void
    {
        $owner = $this->owner();
        $restaurant = fn (Closure $fields): Closure => fn (string $text): TestResponse => $this->actingAs($owner->user)
            ->patchJson(route('api.restaurant.update'), ['name' => ['en' => 'My Place'], 'phone' => '+961 70 123 456', 'currency' => 'USD', ...$fields($text)]);

        $this->assertLimit(MenuTextLimits::RESTAURANT_NAME, 'name.en', $restaurant(fn (string $text): array => ['name' => ['en' => $text]]));
        $this->assertLimit(MenuTextLimits::RESTAURANT_DESCRIPTION, 'description.en', $restaurant(fn (string $text): array => ['description' => ['en' => $text]]));
    }

    public function test_an_offer(): void
    {
        $owner = $this->ownerOn('premium', ['timezone' => 'Asia/Beirut']);
        $offer = fn (Closure $fields): Closure => fn (string $text): TestResponse => $this->actingAs($owner->user)
            ->postJson(route('api.offers.store'), [
                'title' => ['en' => 'Happy hour'],
                'placements' => ['banner'],
                'action' => 'none',
                'show_again' => 'every_visit',
                'delay_seconds' => 0,
                'ad_seconds' => 6,
                'discount_type' => 'none',
                'repeat' => 'always',
                ...$fields($text),
            ]);

        $this->assertLimit(MenuTextLimits::OFFER_TITLE, 'title.en', $offer(fn (string $text): array => ['title' => ['en' => $text]]));
        $this->assertLimit(MenuTextLimits::OFFER_DESCRIPTION, 'description.en', $offer(fn (string $text): array => ['description' => ['en' => $text]]));
    }

    public function test_the_name_given_at_onboarding(): void
    {
        $user = User::factory()->create(['onboarding_step' => 0, 'onboarding_completed_at' => null]);

        $this->assertLimit(MenuTextLimits::RESTAURANT_NAME, 'name', fn (string $text): TestResponse => $this->actingAs($user)
            ->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => $text, 'slug' => 'my-place', 'main_locale' => 'en']));
    }

    public function test_a_restaurant_renamed_in_the_admin_app(): void
    {
        $restaurant = $this->owner(['slug' => 'beit-rami']);
        $this->withToken($this->admin()->createToken('phone')->plainTextToken);

        $this->assertLimit(MenuTextLimits::RESTAURANT_NAME, 'name', fn (string $text): TestResponse => $this
            ->patchJson("/api/admin/restaurants/{$restaurant->id}", ['name' => $text, 'slug' => 'beit-rami', 'phone' => '']));
    }
}
