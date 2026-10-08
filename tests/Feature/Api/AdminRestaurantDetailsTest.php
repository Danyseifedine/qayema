<?php

namespace Tests\Feature\Api;

use App\Models\MenuSession;
use App\Models\PreviousSlug;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * One restaurant from the admin app: its visits and QR scans, its basics
 * (name, link, phone), and its owner's password.
 */
class AdminRestaurantDetailsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withToken($this->admin()->createToken('phone')->plainTextToken);
    }

    public function test_a_restaurant_shows_its_visits_qr_scans_and_phone(): void
    {
        $this->travelTo(now()->setTime(12, 0));
        $restaurant = $this->owner(['phone' => '70 123 456', 'country_code' => 'LB']);
        $visit = fn (string $session, $when, bool $qr = false) => MenuSession::create([
            'restaurant_id' => $restaurant->id, 'session_id' => $session, 'viewed_at' => $when, 'via_qr' => $qr,
        ]);
        $visit('a', now()->subHour(), qr: true);
        $visit('a', now()->subMinutes(30));
        $visit('b', now()->subDays(40), qr: true);

        $this->getJson("/api/admin/restaurants/{$restaurant->id}")
            ->assertOk()
            ->assertJsonPath('data.phone', ['number' => '70 123 456', 'country_code' => 'LB', 'dial' => '+961'])
            ->assertJsonPath('data.stats.views_today', 2)
            ->assertJsonPath('data.stats.views_total', 3)
            ->assertJsonPath('data.stats.visitors_total', 2)
            ->assertJsonPath('data.stats.qr_scans.today', 1)
            ->assertJsonPath('data.stats.qr_scans.total', 2)
            ->assertJsonPath('data.stats.last_visit_at', now()->subMinutes(30)->toIso8601String());
    }

    public function test_a_restaurant_never_visited_has_no_last_visit(): void
    {
        $restaurant = $this->owner(['phone' => null]);

        $this->getJson("/api/admin/restaurants/{$restaurant->id}")
            ->assertJsonPath('data.phone', null)
            ->assertJsonPath('data.stats.views_total', 0)
            ->assertJsonPath('data.stats.last_visit_at', null);
    }

    public function test_the_list_does_not_count_visits(): void
    {
        $this->owner();

        $this->getJson('/api/admin/restaurants')
            ->assertJsonMissingPath('data.0.stats')
            ->assertJsonMissingPath('data.0.phone');
    }

    public function test_the_name_link_and_phone_are_changed_and_the_old_link_forwards(): void
    {
        $restaurant = $this->owner(['name' => ['en' => 'Beit Rami', 'ar' => 'بيت رامي'], 'slug' => 'beit-rami', 'country_code' => 'LB']);

        $this->patchJson("/api/admin/restaurants/{$restaurant->id}", [
            'name' => '  Beit Rami Grill ',
            'slug' => 'Beit Rami Grill',
            'phone' => '+961 70 999 888',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'Beit Rami Grill')
            ->assertJsonPath('data.slug', 'beit-rami-grill')
            ->assertJsonPath('data.phone.number', '+961 70 999 888')
            ->assertJsonPath('data.stats.views_total', 0);

        $restaurant->refresh();
        $this->assertSame(['en' => 'Beit Rami Grill', 'ar' => 'بيت رامي'], $restaurant->getTranslations('name'));
        $this->assertSame('LB', $restaurant->country_code);
        $this->assertTrue(PreviousSlug::where('slug', 'beit-rami')->where('restaurant_id', $restaurant->id)->exists());
        $this->get('/beit-rami')->assertRedirect('/beit-rami-grill');
    }

    public function test_the_phone_may_be_cleared(): void
    {
        $restaurant = $this->owner(['phone' => '70123456', 'slug' => 'beit-rami']);

        $this->patchJson("/api/admin/restaurants/{$restaurant->id}", ['name' => 'Beit Rami', 'slug' => 'beit-rami', 'phone' => ''])
            ->assertOk()
            ->assertJsonPath('data.phone', null);
    }

    public function test_a_taken_or_reserved_link_and_a_bad_phone_are_refused(): void
    {
        $this->owner(['slug' => 'snack-lina']);
        $restaurant = $this->owner(['slug' => 'beit-rami']);

        foreach (['snack-lina', 'admin'] as $slug) {
            $this->patchJson("/api/admin/restaurants/{$restaurant->id}", ['name' => 'X', 'slug' => $slug, 'phone' => '70123456'])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('slug');
        }

        $this->patchJson("/api/admin/restaurants/{$restaurant->id}", ['name' => '', 'slug' => 'beit-rami', 'phone' => '12ab'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'phone']);

        $this->assertSame('beit-rami', $restaurant->fresh()->slug);
    }

    public function test_keeping_its_own_link_is_fine(): void
    {
        $restaurant = $this->owner(['slug' => 'beit-rami']);

        $this->patchJson("/api/admin/restaurants/{$restaurant->id}", ['name' => 'Beit Rami', 'slug' => 'beit-rami', 'phone' => null])
            ->assertOk();
        $this->assertSame(0, PreviousSlug::count());
    }

    public function test_the_owner_gets_a_new_password_and_their_old_one_stops_working(): void
    {
        $restaurant = $this->owner();
        $owner = $restaurant->user;
        $owner->forceFill(['password' => 'the-old-password', 'remember_token' => 'remembered'])->save();

        $this->putJson("/api/admin/restaurants/{$restaurant->id}/owner/password", ['password' => 'a-new-password'])
            ->assertNoContent();

        $owner->refresh();
        $this->assertTrue(Hash::check('a-new-password', $owner->password));
        $this->assertFalse(Hash::check('the-old-password', $owner->password));
        $this->assertNull($owner->remember_token);
    }

    public function test_a_short_password_is_refused(): void
    {
        $restaurant = $this->owner();

        $this->putJson("/api/admin/restaurants/{$restaurant->id}/owner/password", ['password' => 'short'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
    }

    public function test_an_admins_password_is_never_reset_from_here(): void
    {
        $otherAdmin = $this->admin();
        $otherAdmin->forceFill(['password' => 'admin-password'])->save();
        $restaurant = Restaurant::factory()->create(['user_id' => $otherAdmin->id]);

        $this->putJson("/api/admin/restaurants/{$restaurant->id}/owner/password", ['password' => 'a-new-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');
        $this->assertTrue(Hash::check('admin-password', $otherAdmin->fresh()->password));
    }

    public function test_only_admins_reach_these(): void
    {
        $restaurant = $this->owner();
        $this->app['auth']->forgetGuards();
        $owner = User::factory()->create();

        $this->withToken($owner->createToken('phone')->plainTextToken)
            ->putJson("/api/admin/restaurants/{$restaurant->id}/owner/password", ['password' => 'a-new-password'])
            ->assertForbidden();
    }
}
