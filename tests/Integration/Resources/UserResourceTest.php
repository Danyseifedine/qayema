<?php

namespace Tests\Integration\Resources;

use App\Enums\Feature;
use App\Http\Resources\UserResource;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const USER_KEYS = ['id', 'name', 'email', 'role', 'has_completed_onboarding', 'has_password'];

    private const RESTAURANT_KEYS = [
        'id', 'name', 'slug', 'languages', 'second_locale', 'default_locale', 'is_active', 'template_id',
        'logo_url', 'public_url', 'qr_url', 'package', 'lapsed', 'upcoming', 'limits', 'switched_off', 'plan',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://qayema.test']);
        $this->travelTo(CarbonImmutable::parse('2026-06-15 12:00:00', 'UTC'));
    }

    /** @return array<string, mixed> */
    private function resolve(User $user): array
    {
        return (new UserResource($user))->resolve(Request::create('/api/user'));
    }

    public function test_the_restaurant_is_left_out_unless_loaded(): void
    {
        $user = $this->owner()->user->fresh();

        $this->assertSame(self::USER_KEYS, array_keys($this->resolve($user)));
    }

    public function test_a_user_without_a_restaurant_gets_null(): void
    {
        $user = User::factory()->create([
            'name' => 'Rana',
            'email' => 'rana@example.test',
            'onboarding_completed_at' => null,
        ]);

        $this->assertSame([
            'id' => $user->id,
            'name' => 'Rana',
            'email' => 'rana@example.test',
            'role' => 'menu_owner',
            'has_completed_onboarding' => false,
            'has_password' => true,
            'restaurant' => null,
        ], $this->resolve($user->load('restaurant')));
    }

    public function test_a_google_only_admin(): void
    {
        $user = User::factory()->admin()->create(['password' => null, 'onboarding_completed_at' => now()]);

        $data = $this->resolve($user);

        $this->assertSame('admin', $data['role']);
        $this->assertFalse($data['has_password']);
        $this->assertTrue($data['has_completed_onboarding']);
    }

    /** A new restaurant starts on the default package the moment it is created, forever. */
    public function test_an_owner_on_the_default_package_forever(): void
    {
        $restaurant = $this->owner(['slug' => 'aran', 'name' => ['en' => 'Aran', 'ar' => 'آران'], 'second_locale' => 'ar', 'default_locale' => 'ar']);
        $category = Category::factory()->for($restaurant)->create();
        Dish::factory()->for($restaurant)->count(2)->create(['category_id' => $category->id]);

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame(self::RESTAURANT_KEYS, array_keys($data));
        $this->assertSame($restaurant->id, $data['id']);
        $this->assertSame(['en' => 'Aran'], $data['name']);
        $this->assertSame(['en'], $data['languages']);
        $this->assertSame('ar', $data['second_locale']);
        $this->assertSame('en', $data['default_locale']);
        $this->assertTrue($data['is_active']);
        $this->assertNull($data['template_id']);
        $this->assertNull($data['logo_url']);
        $this->assertSame('https://qayema.test/aran', $data['public_url']);
        $this->assertSame('https://qayema.test/aran?qr=1', $data['qr_url']);
        $this->assertSame([
            'slug' => 'free',
            'name' => ['en' => 'Free', 'ar' => 'مجاني'],
            'is_contact_only' => false,
            'starts_at' => '2026-06-15T12:00:00+00:00',
            'ends_at' => null,
            'days_left' => null,
        ], $data['package']);
        $this->assertNull($data['lapsed']);
        $this->assertNull($data['upcoming']);
        $this->assertSame([
            'dishes' => ['used' => 2, 'limit' => 40],
            'categories' => ['used' => 1, 'limit' => 8],
            'social_links' => ['used' => 0, 'limit' => 1],
        ], $data['limits']);
        $this->assertSame([], $data['switched_off']);
        $this->assertSame(array_column(Feature::flags(), 'value'), array_keys($data['plan']));
        $this->assertSame(array_fill_keys(array_column(Feature::flags(), 'value'), false), $data['plan']);
    }

    public function test_an_active_package_with_an_end_sends_its_dates_and_days_left(): void
    {
        $restaurant = $this->ownerOn('premium', [
            'package_started_at' => '2026-06-01 00:00:00',
            'package_ends_at' => '2026-06-20 00:00:00',
            'switched_off' => ['qr'],
        ]);

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame('premium', $data['package']['slug']);
        $this->assertSame('2026-06-01T00:00:00+00:00', $data['package']['starts_at']);
        $this->assertSame('2026-06-20T00:00:00+00:00', $data['package']['ends_at']);
        $this->assertSame(5, $data['package']['days_left']);
        $this->assertSame(['qr'], $data['switched_off']);
        $this->assertTrue($data['plan']['ordering']);
        $this->assertSame(['en', 'ar'], $data['languages']);
    }

    /** "Ends today" is 0, not a negative number, and part of a day rounds up. */
    public function test_days_left_rounds_up_and_never_goes_below_zero(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_ends_at' => '2026-06-15 13:00:00']);

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame(1, $data['package']['days_left']);
    }

    public function test_an_ended_package_reports_the_default_and_what_lapsed(): void
    {
        $restaurant = $this->ownerOn('pro', [
            'package_started_at' => '2026-01-01 00:00:00',
            'package_ends_at' => '2026-06-01 00:00:00',
        ]);

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame('free', $data['package']['slug']);
        $this->assertNull($data['package']['starts_at']);
        $this->assertNull($data['package']['ends_at']);
        $this->assertNull($data['package']['days_left']);
        $this->assertSame([
            'slug' => 'pro',
            'name' => ['en' => 'Pro', 'ar' => 'برو'],
            'ended_at' => '2026-06-01T00:00:00+00:00',
        ], $data['lapsed']);
        $this->assertNull($data['upcoming']);
        $this->assertSame(40, $data['limits']['dishes']['limit']);
    }

    public function test_a_package_still_to_start_is_upcoming(): void
    {
        $restaurant = $this->ownerOn('custom', [
            'package_started_at' => '2026-07-01 00:00:00',
            'package_ends_at' => null,
        ]);

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame('free', $data['package']['slug']);
        $this->assertNull($data['lapsed']);
        $this->assertSame([
            'slug' => 'custom',
            'name' => ['en' => 'Custom', 'ar' => 'مخصّص'],
            'starts_at' => '2026-07-01T00:00:00+00:00',
            'ends_at' => null,
        ], $data['upcoming']);
    }

    public function test_an_unlimited_package_sends_null_limits_and_is_contact_only(): void
    {
        $restaurant = $this->ownerOn('custom');

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertTrue($data['package']['is_contact_only']);
        $this->assertNull($data['limits']['dishes']['limit']);
        $this->assertNull($data['limits']['categories']['limit']);
        $this->assertNull($data['limits']['social_links']['limit']);
        $this->assertNotContains(false, $data['plan']);
        $this->assertTrue(Package::findBySlug('custom')->is($restaurant->effectivePackage()));
    }
}
