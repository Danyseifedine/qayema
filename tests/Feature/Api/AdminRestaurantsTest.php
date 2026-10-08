<?php

namespace Tests\Feature\Api;

use App\Models\Package;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The admin phone app's restaurants (`api/admin/restaurants`, `/packages`):
 * listing and finding them, opening one with its owner, switching a menu on
 * or off, changing its package and adding time.
 */
class AdminRestaurantsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->admin();
        $this->withToken($this->admin->createToken('phone')->plainTextToken);
    }

    /** @return array<string, mixed> */
    private function newRestaurant(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Beit Rami',
            'slug' => '',
            'owner_name' => 'Rami Haddad',
            'username' => 'Rami',
            'email' => '',
            'password' => 'a-good-password',
            'package_id' => Package::findBySlug('pro')->id,
            'months' => 3,
            'note' => 'Paid 3 months in cash',
        ], $overrides);
    }

    public function test_only_an_admin_token_reaches_them(): void
    {
        $owner = $this->owner()->user;

        $this->withToken($owner->createToken('phone')->plainTextToken)
            ->getJson('/api/admin/restaurants')
            ->assertForbidden();
    }

    public function test_the_list_shows_each_restaurant_with_its_owner_and_package(): void
    {
        $restaurant = $this->ownerOn('pro', [
            'name' => ['en' => 'Cedar & Salt', 'ar' => 'أرز وملح'],
            'slug' => 'cedar-salt',
            'package_ends_at' => now()->addDays(10)->subHour(),
        ]);
        $restaurant->user->update(['username' => 'cedar', 'email' => null]);

        $this->getJson('/api/admin/restaurants')
            ->assertOk()
            ->assertJsonPath('data.0.id', $restaurant->id)
            ->assertJsonPath('data.0.name', 'Cedar & Salt')
            ->assertJsonPath('data.0.public_url', rtrim((string) config('app.url'), '/').'/cedar-salt')
            ->assertJsonPath('data.0.is_active', true)
            ->assertJsonPath('data.0.owner.username', 'cedar')
            ->assertJsonPath('data.0.owner.email', null)
            ->assertJsonPath('data.0.package.name', 'Pro')
            ->assertJsonPath('data.0.package.status', 'active')
            ->assertJsonPath('data.0.package.days_left', 10)
            ->assertJsonPath('data.0.package.in_force.name', 'Pro')
            ->assertJsonMissingPath('data.0.dishes_count')
            ->assertJsonStructure(['meta' => ['current_page', 'last_page', 'total'], 'links' => ['next']]);
    }

    public function test_the_list_reads_in_a_fixed_number_of_queries(): void
    {
        foreach (range(1, 5) as $i) {
            $this->ownerOn('pro', ['package_ends_at' => now()->addDays($i)]);
        }

        $this->getJson('/api/admin/restaurants')->assertOk();

        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });
        $this->getJson('/api/admin/restaurants')->assertOk()->assertJsonCount(5, 'data');
        $few = $queries;

        foreach (range(1, 5) as $i) {
            $this->ownerOn('pro', ['package_ends_at' => now()->addDays($i)]);
        }
        $queries = 0;
        $this->getJson('/api/admin/restaurants')->assertOk()->assertJsonCount(10, 'data');

        $this->assertSame($few, $queries);
    }

    public function test_search_finds_a_restaurant_by_name_link_or_owner(): void
    {
        $rami = $this->owner(['name' => ['en' => 'Beit Rami'], 'slug' => 'beit-rami']);
        $rami->user->update(['name' => 'Rami Haddad', 'username' => 'rami.h', 'email' => 'rami@example.test']);
        $other = $this->owner(['name' => ['en' => 'Snack Lina'], 'slug' => 'snack-lina']);

        foreach (['Beit', 'beit-rami', 'Haddad', 'rami.h', 'rami@example'] as $search) {
            $this->getJson('/api/admin/restaurants?search='.urlencode($search))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $rami->id);
        }

        $this->getJson('/api/admin/restaurants?search=100%25')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/admin/restaurants?search=lina')->assertJsonPath('data.0.id', $other->id);
    }

    public function test_filters_split_menus_on_and_off(): void
    {
        $on = $this->owner();
        $off = $this->owner(['is_active' => false]);

        $this->getJson('/api/admin/restaurants?filter=active')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $on->id);
        $this->getJson('/api/admin/restaurants?filter=inactive')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $off->id);
        $this->getJson('/api/admin/restaurants?filter=all')->assertJsonCount(2, 'data');
        $this->getJson('/api/admin/restaurants?filter=gone')->assertUnprocessable()->assertJsonValidationErrors('filter');
    }

    public function test_ending_lists_packages_in_force_soonest_first_and_ended_latest_first(): void
    {
        $later = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(20)]);
        $sooner = $this->ownerOn('premium', ['package_ends_at' => now()->addDays(2)]);
        $this->ownerOn('pro', ['package_ends_at' => null]);
        $longAgo = $this->ownerOn('pro', ['package_ends_at' => now()->subDays(40)]);
        $lately = $this->ownerOn('pro', ['package_ends_at' => now()->subDay()]);
        $this->ownerOn('pro', ['package_started_at' => now()->addWeek(), 'package_ends_at' => now()->addMonth()]);

        $this->getJson('/api/admin/restaurants?filter=ending')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $sooner->id)
            ->assertJsonPath('data.0.package.days_left', 2)
            ->assertJsonPath('data.1.id', $later->id);

        $this->getJson('/api/admin/restaurants?filter=ended')
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $lately->id)
            ->assertJsonPath('data.0.package.status', 'expired')
            ->assertJsonPath('data.0.package.days_left', null)
            ->assertJsonPath('data.0.package.in_force.id', Package::default()->id)
            ->assertJsonPath('data.1.id', $longAgo->id);
    }

    public function test_one_restaurant_also_counts_its_dishes_and_categories(): void
    {
        $restaurant = $this->owner();

        $this->getJson("/api/admin/restaurants/{$restaurant->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $restaurant->id)
            ->assertJsonPath('data.dishes_count', 0)
            ->assertJsonPath('data.categories_count', 0);

        $this->getJson('/api/admin/restaurants/999999')->assertNotFound();
    }

    public function test_opening_a_restaurant_makes_the_owner_and_the_restaurant_on_its_package(): void
    {
        $this->travelTo(now()->startOfDay()->addHours(10));

        $response = $this->postJson('/api/admin/restaurants', $this->newRestaurant())
            ->assertCreated()
            ->assertJsonPath('data.name', 'Beit Rami')
            ->assertJsonPath('data.slug', 'beit-rami')
            ->assertJsonPath('data.owner.username', 'rami')
            ->assertJsonPath('data.owner.email', null)
            ->assertJsonPath('data.package.name', 'Pro')
            ->assertJsonPath('data.package.ends_at', now()->addMonthsNoOverflow(3)->toIso8601String());

        $restaurant = Restaurant::findOrFail($response->json('data.id'));
        $owner = $restaurant->user;
        $this->assertTrue($owner->isMenuOwner());
        $this->assertTrue(Hash::check('a-good-password', $owner->password));
        $this->assertSame(1, $owner->onboarding_step);
        $this->assertSame('ar', $restaurant->second_locale);

        $history = $restaurant->packageChanges()->sole();
        $this->assertSame('Paid 3 months in cash', $history->note);
        $this->assertSame($this->admin->id, $history->changed_by);
    }

    public function test_a_restaurant_opened_without_months_runs_forever(): void
    {
        $this->postJson('/api/admin/restaurants', $this->newRestaurant(['months' => null, 'slug' => 'My Own Link']))
            ->assertCreated()
            ->assertJsonPath('data.slug', 'my-own-link')
            ->assertJsonPath('data.package.ends_at', null)
            ->assertJsonPath('data.package.days_left', null);
    }

    public function test_the_owner_signs_in_with_a_username_or_an_email(): void
    {
        $this->postJson('/api/admin/restaurants', $this->newRestaurant(['username' => '', 'email' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username', 'email']);

        $this->postJson('/api/admin/restaurants', $this->newRestaurant(['username' => '', 'email' => 'owner@example.test']))
            ->assertCreated()
            ->assertJsonPath('data.owner.email', 'owner@example.test')
            ->assertJsonPath('data.owner.username', null);
    }

    public function test_a_taken_link_username_or_email_is_refused_and_nothing_is_made(): void
    {
        $this->owner(['slug' => 'beit-rami'])->user->update(['username' => 'rami', 'email' => 'taken@example.test']);
        $users = User::count();

        $this->postJson('/api/admin/restaurants', $this->newRestaurant(['email' => 'taken@example.test']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug', 'username', 'email']);

        $this->postJson('/api/admin/restaurants', $this->newRestaurant(['slug' => 'admin', 'username' => 'new.one', 'email' => '']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);

        $this->assertSame($users, User::count());
    }

    public function test_opening_a_restaurant_checks_every_field(): void
    {
        $this->postJson('/api/admin/restaurants', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'slug', 'owner_name', 'username', 'password', 'package_id']);

        $this->postJson('/api/admin/restaurants', $this->newRestaurant(['password' => 'short', 'months' => 2, 'package_id' => 999]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password', 'months', 'package_id']);
    }

    public function test_a_menu_is_switched_off_and_on(): void
    {
        $restaurant = $this->owner();

        $this->patchJson("/api/admin/restaurants/{$restaurant->id}/active", ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.is_active', false);
        $this->assertFalse($restaurant->fresh()->is_active);

        $this->patchJson("/api/admin/restaurants/{$restaurant->id}/active", ['is_active' => true])
            ->assertJsonPath('data.is_active', true);
        $this->patchJson("/api/admin/restaurants/{$restaurant->id}/active", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('is_active');
    }

    public function test_changing_the_package_starts_it_today_for_the_months_given(): void
    {
        $restaurant = $this->owner();
        $premium = Package::findBySlug('premium');

        $this->putJson("/api/admin/restaurants/{$restaurant->id}/package", [
            'package_id' => $premium->id,
            'months' => 6,
            'note' => 'Upgraded on the phone',
        ])
            ->assertOk()
            ->assertJsonPath('data.package.name', 'Premium')
            ->assertJsonPath('data.package.in_force.id', $premium->id);

        $restaurant->refresh();
        $this->assertTrue($restaurant->package_ends_at->isSameDay(now()->addMonthsNoOverflow(6)));
        $this->assertSame('Upgraded on the phone', $restaurant->packageChanges()->first()->note);

        $this->putJson("/api/admin/restaurants/{$restaurant->id}/package", ['package_id' => $premium->id])
            ->assertOk()
            ->assertJsonPath('data.package.ends_at', null);
    }

    public function test_adding_a_month_moves_the_end_or_restarts_an_ended_package(): void
    {
        // The test and the request must read the same second.
        $this->freezeTime();
        $running = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(5)]);
        $ended = $this->ownerOn('pro', ['package_ends_at' => now()->subDays(3)]);

        $this->postJson("/api/admin/restaurants/{$running->id}/package/extend", ['months' => 1])
            ->assertOk()
            ->assertJsonPath('data.package.ends_at', now()->addDays(5)->addMonthsNoOverflow(1)->toIso8601String());

        $this->postJson("/api/admin/restaurants/{$ended->id}/package/extend", ['months' => 1, 'note' => 'Renewed'])
            ->assertOk()
            ->assertJsonPath('data.package.status', 'active')
            ->assertJsonPath('data.package.in_force.name', 'Pro');
        $this->assertTrue($ended->fresh()->package_ends_at->isSameDay(now()->addMonthsNoOverflow(1)));

        $this->postJson("/api/admin/restaurants/{$running->id}/package/extend", ['months' => null])
            ->assertOk()
            ->assertJsonPath('data.package.ends_at', null);
    }

    public function test_a_package_that_runs_forever_has_nothing_to_extend(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_ends_at' => null]);

        $this->postJson("/api/admin/restaurants/{$restaurant->id}/package/extend", ['months' => 1])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('months');

        $this->postJson("/api/admin/restaurants/{$restaurant->id}/package/extend", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('months');
    }

    public function test_the_packages_are_listed_in_order_with_the_ones_not_offered(): void
    {
        Package::findBySlug('custom')->update(['is_active' => false]);

        $this->getJson('/api/admin/packages')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Free')
            ->assertJsonPath('data.0.is_default', true)
            ->assertJsonPath('data.3.name', 'Custom')
            ->assertJsonPath('data.3.offered', false)
            ->assertJsonCount(4, 'data');
    }
}
