<?php

namespace Tests\Feature\Packages;

use App\Enums\Feature;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Packages\Pages\ListPackages;
use App\Models\Category;
use App\Models\Dish;
use App\Models\FeatureGrant;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Premium's dishes and categories read "Unlimited" while 1,000 each holds as
 * fair use, stated under the pricing cards and in the Terms, and named
 * plainly to an owner who reaches it.
 */
class FairUseTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_a_premium_owner_is_shown_unlimited_dishes_and_categories(): void
    {
        $restaurant = $this->ownerOn('premium');

        $this->actingAs($restaurant->user)->getJson(route('api.user'))
            ->assertOk()
            ->assertJsonPath('data.restaurant.limits.dishes.limit', null)
            ->assertJsonPath('data.restaurant.limits.categories.limit', null)
            // Not every Premium limit is fair use.
            ->assertJsonPath('data.restaurant.limits.social_links.limit', 10);

        $premium = collect($this->actingAs($restaurant->user)->getJson('/api/packages')->json('data'))->firstWhere('slug', 'premium');
        $this->assertNull($premium['features']['dish_limit']);
        $this->assertNull($premium['features']['category_limit']);
        $this->assertSame(10, $premium['features']['social_link_limit']);

        // What holds is the number.
        $this->assertSame(1000, $restaurant->dish_limit);
        $this->assertSame(1000, $restaurant->category_limit);
    }

    public function test_the_fair_use_number_holds_and_says_what_it_is(): void
    {
        $this->premiumAllows(2);
        $restaurant = $this->ownerOn('premium');
        $categories = Category::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);
        Dish::factory()->count(2)->create(['restaurant_id' => $restaurant->id, 'category_id' => $categories[0]->id]);

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'Third'], 'price' => 5, 'category_id' => $categories[0]->id])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'You have reached the fair-use limit of 2 dishes. Contact us if you need more.');

        $this->actingAs($restaurant->user)
            ->withHeader('Accept-Language', 'ar')
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'Third']])
            ->assertUnprocessable()
            ->assertJsonPath('errors.name.0', 'وصلت إلى حد الاستخدام العادل: 2 قسم. تواصل معنا إن احتجت إلى المزيد.');
    }

    public function test_a_package_without_fair_use_keeps_its_plain_message(): void
    {
        $restaurant = $this->owner();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        Dish::factory()->count(40)->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);

        $this->actingAs($restaurant->user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'One more'], 'price' => 5, 'category_id' => $category->id])
            ->assertJsonPath('errors.name.0', 'You have reached your plan limit of 40 dishes.');
    }

    public function test_a_grant_still_adds_on_top_of_the_fair_use_number(): void
    {
        $this->premiumAllows(2);
        $restaurant = $this->ownerOn('premium');
        FeatureGrant::factory()->create(['restaurant_id' => $restaurant->id, 'feature' => Feature::DishLimit, 'value' => 3]);

        $this->assertSame(5, $restaurant->fresh()->dish_limit);
        $this->assertNull($restaurant->fresh()->entitlements()->shownLimit(Feature::DishLimit));
    }

    public function test_the_pricing_page_states_the_number_behind_unlimited(): void
    {
        $this->get('/pricing')
            ->assertOk()
            ->assertSee('Unlimited dishes*')
            ->assertSee('* Fair use on Premium: up to 1,000 dishes and 1,000 categories.')
            ->assertSee('What does &quot;Unlimited&quot; mean?', false);

        $this->get('/ar')->assertOk()->assertSee('* الاستخدام العادل في باقة مميّز: حتى 1,000 طبق و1,000 قسم.');
    }

    public function test_the_terms_explain_fair_use_in_both_languages(): void
    {
        $this->get('/terms-of-service')->assertOk()->assertSee('Fair use.')->assertSee('a fair-use limit applies');
        $this->get('/ar/terms-of-service')->assertOk()->assertSee('الاستخدام العادل.');
    }

    public function test_an_unticked_or_empty_limit_is_never_fair_use(): void
    {
        $custom = Package::findBySlug('custom');
        $custom->update(['fair_use' => ['dish_limit']]);

        // Empty is truly unlimited, ticked or not.
        $this->assertFalse($custom->fresh()->isFairUse(Feature::DishLimit));
        $this->assertFalse(Package::findBySlug('premium')->isFairUse(Feature::SocialLinkLimit));
    }

    public function test_the_admin_ticks_which_limits_read_as_unlimited(): void
    {
        $this->actingAs($this->admin());
        $pro = Package::findBySlug('pro');

        Livewire::test(EditPackage::class, ['record' => $pro->getRouteKey()])
            ->fillForm(['fair_use' => ['dish_limit']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($pro->fresh()->isFairUse(Feature::DishLimit));
        Livewire::test(ListPackages::class)->assertSee('150 (fair use)')->assertSee('1000 (fair use)');
    }

    private function premiumAllows(int $count): void
    {
        $premium = Package::findBySlug('premium');
        $premium->update(['features' => [...$premium->features, 'dish_limit' => $count, 'category_limit' => $count]]);
    }
}
