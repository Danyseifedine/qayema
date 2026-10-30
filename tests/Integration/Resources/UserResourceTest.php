<?php

namespace Tests\Integration\Resources;

use App\Enums\Feature;
use App\Http\Resources\UserResource;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class UserResourceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const USER_KEYS = ['name', 'username', 'email', 'has_completed_onboarding', 'has_password', 'impersonation'];

    private const RESTAURANT_KEYS = [
        'id', 'languages', 'main_locale', 'second_locale', 'default_locale', 'template_id', 'public_url',
        'package', 'lapsed', 'upcoming', 'limits', 'dishes_without_photo', 'pos_connected', 'switched_off', 'ordering', 'plan', 'turned_off', 'writing_help',
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
            'name' => 'Rana',
            'username' => null,
            'email' => 'rana@example.test',
            'has_completed_onboarding' => false,
            'has_password' => true,
            'impersonation' => null,
            'restaurant' => null,
        ], $this->resolve($user->load('restaurant')));
    }

    public function test_an_account_made_with_a_username_has_no_email(): void
    {
        $user = User::factory()->withUsername('beit.rami')->create();

        $data = $this->resolve($user);

        $this->assertSame('beit.rami', $data['username']);
        $this->assertNull($data['email']);
        $this->assertTrue($data['has_password']);
    }

    public function test_a_google_only_admin(): void
    {
        $user = User::factory()->admin()->create(['password' => null, 'onboarding_completed_at' => now()]);

        $data = $this->resolve($user);

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
        $this->assertSame(['en'], $data['languages']);
        $this->assertSame('ar', $data['second_locale']);
        $this->assertSame('en', $data['default_locale']);
        $this->assertNull($data['template_id']);
        $this->assertSame('https://qayema.test/aran', $data['public_url']);
        $this->assertSame([
            'slug' => 'free',
            'tier' => 'bronze',
            'name' => ['en' => 'Free', 'ar' => 'مجاني'],
            'is_contact_only' => false,
            'ends_at' => null,
            'days_left' => null,
        ], $data['package']);
        $this->assertNull($data['lapsed']);
        $this->assertNull($data['upcoming']);
        $this->assertSame([
            'dishes' => ['used' => 2, 'limit' => 40],
            'categories' => ['used' => 1, 'limit' => 8],
            'social_links' => ['used' => 0, 'limit' => 1],
            'builds' => ['used' => 0, 'limit' => 3],
        ], $data['limits']);
        $this->assertSame(2, $data['dishes_without_photo']);
        $this->assertSame([], $data['switched_off']);
        $this->assertSame(['mode' => 'whatsapp', 'types' => ['delivery', 'pickup'], 'dine_in' => 'menu', 'whatsapp_number' => true, 'whatsapp_fields' => ['away' => ['name' => 'off', 'phone' => 'off', 'address' => 'off'], 'table' => ['name' => 'off', 'phone' => 'off']]], $data['ordering']);
        $this->assertSame(array_column(Feature::flags(), 'value'), array_keys($data['plan']));
        $this->assertSame(array_fill_keys(array_column(Feature::flags(), 'value'), false), $data['plan']);
        $this->assertSame([], $data['turned_off']);
    }

    public function test_an_active_package_with_an_end_sends_its_end_and_days_left(): void
    {
        $restaurant = $this->ownerOn('premium', [
            'package_started_at' => '2026-06-01 00:00:00',
            'package_ends_at' => '2026-06-20 00:00:00',
            'switched_off' => ['qr'],
        ]);

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame('premium', $data['package']['slug']);
        $this->assertSame('2026-06-20T00:00:00+00:00', $data['package']['ends_at']);
        $this->assertSame(5, $data['package']['days_left']);
        $this->assertSame(['qr'], $data['switched_off']);
        $this->assertTrue($data['plan']['ordering']);
        $this->assertSame(['en', 'ar'], $data['languages']);
    }

    public function test_a_feature_an_admin_turned_off_is_named_and_locked_in_the_plan(): void
    {
        $restaurant = $this->ownerOn('premium');
        FeatureGrant::factory()->for($restaurant)->setTo(Feature::Ordering, 0)->create();

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame(['ordering'], $data['turned_off']);
        $this->assertFalse($data['plan']['ordering']);
        $this->assertTrue($data['plan']['qr_studio']);
    }

    /** Ordering in the menu shows only while the package includes it; the choice is kept. */
    public function test_the_ordering_mode_follows_the_package(): void
    {
        $restaurant = $this->ownerOn('premium', ['order_mode' => 'menu', 'order_types' => ['pickup'], 'dine_in_mode' => 'whatsapp', 'phone' => null]);

        $this->assertSame(
            ['mode' => 'menu', 'types' => ['pickup'], 'dine_in' => 'whatsapp', 'whatsapp_number' => false, 'whatsapp_fields' => ['away' => ['name' => 'off', 'phone' => 'off', 'address' => 'off'], 'table' => ['name' => 'off', 'phone' => 'off']]],
            $this->resolve($restaurant->user->load('restaurant'))['restaurant']['ordering'],
        );

        $restaurant = $this->ownerOn('pro', ['order_mode' => 'menu']);

        $this->assertSame('whatsapp', $this->resolve($restaurant->user->load('restaurant'))['restaurant']['ordering']['mode']);
        $this->assertSame('menu', $restaurant->fresh()->order_mode);
    }

    /** "Ends today" is 0, not a negative number, and part of a day rounds up. */
    public function test_days_left_rounds_up_and_never_goes_below_zero(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_ends_at' => '2026-06-15 13:00:00']);

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame(1, $data['package']['days_left']);
    }

    public function test_an_ended_package_is_kept_and_says_when_the_menu_goes_offline(): void
    {
        $restaurant = $this->ownerOn('pro', [
            'package_started_at' => '2026-01-01 00:00:00',
            'package_ends_at' => '2026-06-01 00:00:00',
        ]);

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame('pro', $data['package']['slug']);
        $this->assertNull($data['package']['ends_at']);
        $this->assertNull($data['package']['days_left']);
        $this->assertSame([
            'slug' => 'pro',
            'tier' => 'silver',
            'name' => ['en' => 'Pro', 'ar' => 'برو'],
            'ended_at' => '2026-06-01T00:00:00+00:00',
            'offline_at' => '2026-06-04T00:00:00+00:00',
            'offline' => true,
        ], $data['lapsed']);
        $this->assertNull($data['upcoming']);
        $this->assertSame(150, $data['limits']['dishes']['limit']);
    }

    public function test_in_its_grace_days_an_ended_package_says_the_menu_is_still_online(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_ends_at' => now()->subDay()]);

        $lapsed = $this->resolve($restaurant->user->load('restaurant'))['restaurant']['lapsed'];

        $this->assertFalse($lapsed['offline']);
        $this->assertSame(now()->addDays(2)->toIso8601String(), $lapsed['offline_at']);
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
            'tier' => 'gold',
            'name' => ['en' => 'Custom', 'ar' => 'مخصّص'],
            'starts_at' => '2026-07-01T00:00:00+00:00',
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
        // Every flag but build your own and connect to POS, which an admin
        // switches on per restaurant.
        $this->assertNotContains(false, array_diff_key($data['plan'], ['build_your_own' => true, 'pos_connection' => true]));
        $this->assertFalse($data['plan']['build_your_own']);
        $this->assertFalse($data['plan']['pos_connection']);
        $this->assertTrue(Package::findBySlug('custom')->is($restaurant->effectivePackage()));
    }

    /** Home's checklist counts the dishes still without a photo, only this restaurant's (a build's has an icon instead). */
    public function test_it_counts_the_dishes_without_a_photo(): void
    {
        Storage::fake(config('media-library.disk_name'));
        $restaurant = $this->owner();
        $restaurant->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');
        $category = Category::factory()->for($restaurant)->create();
        [$pictured] = Dish::factory()->for($restaurant)->count(3)->create(['category_id' => $category->id]);
        $pictured->addMedia(UploadedFile::fake()->image('kafta.jpg'))->toMediaCollection('image');
        $other = $this->owner();
        Dish::factory()->for($other)->create(['category_id' => Category::factory()->for($other)->create()->id]);
        DishBuild::factory()->for(Dish::factory()->for($restaurant)->create(['category_id' => null]))->create();

        $data = $this->resolve($restaurant->user->load('restaurant'))['restaurant'];

        $this->assertSame(4, $data['limits']['dishes']['used']);
        $this->assertSame(2, $data['dishes_without_photo']);
    }
}
