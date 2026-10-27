<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class PackagesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_it_requires_authentication(): void
    {
        $this->getJson(route('api.packages.index'))->assertUnauthorized();
    }

    public function test_it_lists_every_package_in_order_with_its_feature_values(): void
    {
        $owner = $this->owner();

        $response = $this->actingAs($owner->user)->getJson(route('api.packages.index'))->assertOk();

        $this->assertSame(['free', 'pro', 'premium', 'custom'], array_column($response->json('data'), 'slug'));

        $response->assertJsonStructure([
            'data' => [[
                'id', 'slug', 'price_cents', 'currency', 'is_contact_only', 'is_default',
                'name' => ['en', 'ar'],
                'description' => ['en', 'ar'],
                'features' => ['dish_limit', 'category_limit', 'social_link_limit', 'qr_studio'],
            ]],
            'meta' => ['current'],
        ]);
    }

    public function test_an_unlimited_limit_comes_through_as_null_and_a_flag_as_a_boolean(): void
    {
        $owner = $this->owner();

        $packages = collect($this->actingAs($owner->user)->getJson(route('api.packages.index'))->json('data'))
            ->keyBy('slug');

        $this->assertNull($packages['custom']['features']['dish_limit'], 'Custom is unlimited.');
        $this->assertNull($packages['custom']['price_cents'], 'Custom has no published price.');
        $this->assertTrue($packages['custom']['is_contact_only']);

        $this->assertSame(40, $packages['free']['features']['dish_limit']);
        $this->assertFalse($packages['free']['features']['qr_studio']);
        $this->assertFalse($packages['pro']['features']['qr_studio']);
        $this->assertTrue($packages['premium']['features']['qr_studio']);
    }

    public function test_the_meta_reports_the_package_in_force(): void
    {
        $owner = $this->ownerOn('pro', ['package_ends_at' => now()->addMonth()]);

        $this->actingAs($owner->user)->getJson(route('api.packages.index'))
            ->assertOk()
            ->assertJsonPath('meta.current', 'pro');
    }

    public function test_the_owner_is_told_their_own_price_when_an_admin_set_one(): void
    {
        $owner = $this->ownerOn('premium');

        $this->actingAs($owner->user)->getJson(route('api.packages.index'))
            ->assertJsonPath('meta.your_price_cents', null);

        $owner->update(['price_cents' => 600]);

        $this->actingAs($owner->user->refresh())->getJson(route('api.packages.index'))
            ->assertJsonPath('meta.your_price_cents', 600);
    }

    public function test_an_expired_package_is_still_the_current_one(): void
    {
        $owner = $this->ownerOn('premium', ['package_ends_at' => now()->subDays(10)]);

        $this->actingAs($owner->user)->getJson(route('api.packages.index'))
            ->assertOk()
            ->assertJsonPath('meta.current', 'premium');
    }

    public function test_a_user_without_a_restaurant_still_sees_the_catalog(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.packages.index'))
            ->assertOk()
            ->assertJsonCount(Package::query()->count(), 'data')
            ->assertJsonPath('meta.current', null);
    }

    public function test_a_package_no_longer_offered_is_not_listed(): void
    {
        Package::query()->where('slug', 'pro')->firstOrFail()->update(['is_active' => false]);
        $owner = $this->owner();

        $response = $this->actingAs($owner->user)->getJson(route('api.packages.index'))->assertOk();

        $this->assertSame(['free', 'premium', 'custom'], array_column($response->json('data'), 'slug'));
    }

    /** Their own package stays in view, beside what they could ask for. */
    public function test_an_owner_still_sees_their_own_package_once_it_is_no_longer_offered(): void
    {
        $owner = $this->ownerOn('pro');
        Package::query()->where('slug', 'pro')->firstOrFail()->update(['is_active' => false]);

        $this->actingAs($owner->user)->getJson(route('api.packages.index'))
            ->assertOk()
            ->assertJsonPath('meta.current', 'pro')
            ->assertJsonPath('data.1.slug', 'pro');
        // They keep what it gives until it ends.
        $this->assertSame(150, $owner->fresh()->limitOf(Feature::DishLimit));
    }

    public function test_a_cards_own_lines_come_with_each_package(): void
    {
        $owner = $this->owner();

        $packages = collect($this->actingAs($owner->user)->getJson(route('api.packages.index'))->json('data'))->keyBy('slug');

        $this->assertSame(['A menu design made for your brand', 'Limits set for your group of restaurants', 'Direct help from our team'], $packages['custom']['highlights']['en']);
        $this->assertSame('تصميم قائمة خاص بعلامتك', $packages['custom']['highlights']['ar'][0]);
        $this->assertSame(['en' => [], 'ar' => []], $packages['pro']['highlights'], 'None written: the card works them out.');
    }
}
