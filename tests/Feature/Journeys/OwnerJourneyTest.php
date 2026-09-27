<?php

namespace Tests\Feature\Journeys;

use App\Enums\Feature;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\FeatureGrantsRelationManager;
use App\Mail\ContactMessageReceived;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Livewire;
use Mockery;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * Whole flows across many endpoints, the way a real owner would go through
 * them. These are slower and broader than the per-endpoint tests on purpose.
 */
class OwnerJourneyTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Template::factory()->withSettings([['key' => 'primary_color', 'type' => 'color', 'default' => '#C8A85A']])->create(['slug' => 'classic', 'sort_order' => 0]);
    }

    private function signUpWithGoogle(string $email): User
    {
        $googleUser = Mockery::mock(SocialiteUser::class);
        $googleUser->shouldReceive('getId')->andReturn('g-'.md5($email));
        $googleUser->shouldReceive('getEmail')->andReturn($email);
        $googleUser->shouldReceive('getName')->andReturn('Journey Owner');
        $googleUser->shouldReceive('getAvatar')->andReturn(null);
        $googleUser->token = 't';
        $googleUser->refreshToken = null;
        $googleUser->expiresIn = null;
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($googleUser);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))->assertRedirect(route('onboarding'));

        return User::firstWhere('email', $email);
    }

    private function onboard(User $user, string $slug): Restaurant
    {
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'Journey Diner', 'slug' => $slug, 'default_locale' => 'en'])->assertOk();
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 2, 'phone' => '+96170123456', 'currency' => 'USD'])->assertOk();
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 3, 'logo_key' => '11111111-1111-1111-1111-111111111111'])->assertOk()->assertJson(['completed' => true]);

        return $user->fresh()->restaurant;
    }

    public function test_signup_onboard_pick_template_build_menu_hit_limit_get_more_go_live(): void
    {
        Package::default()->setFeature(Feature::DishLimit, 3);
        $user = $this->signUpWithGoogle('journey@example.com');
        $restaurant = $this->onboard($user, 'journey');

        // The shell says: no template yet, dashboard locked.
        $this->actingAs($user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.template_id', null);
        $this->get('/journey')->assertNotFound();

        // Pick the free template.
        $classic = Template::firstWhere('slug', 'classic');
        $this->actingAs($user)->postJson(route('api.templates.select'), ['template_id' => $classic->id])->assertOk();

        // Build the menu up to the limit.
        $category = $this->actingAs($user)->postJson(route('api.categories.store'), ['name' => ['en' => 'Mezze']])->assertCreated()->json('data.id');
        foreach (['Hummus', 'Tabbouleh', 'Fattoush'] as $dish) {
            $this->actingAs($user)->postJson(route('api.dishes.store'), ['name' => ['en' => $dish], 'price' => 5, 'category_id' => $category])->assertCreated();
        }
        $this->actingAs($user)->postJson(route('api.dishes.store'), ['name' => ['en' => 'Fourth'], 'price' => 5, 'category_id' => $category])->assertStatus(422);

        // The admin grants two more slots.
        $this->actingAs($this->admin());
        Livewire::test(FeatureGrantsRelationManager::class, ['ownerRecord' => $restaurant, 'pageClass' => EditRestaurant::class])
            ->callAction(TestAction::make('create')->table(), data: ['feature' => Feature::DishLimit->value, 'value' => 2, 'source' => 'admin']);

        $this->actingAs($user)->postJson(route('api.dishes.store'), ['name' => ['en' => 'Fourth'], 'price' => 5, 'category_id' => $category])->assertCreated();
        $this->actingAs($user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.limits.dishes', ['used' => 4, 'limit' => 5]);

        // Guests see all four; the visit is counted.
        $this->get('/journey?qr=1')->assertOk()->assertSee('Hummus')->assertSee('Fourth');
        $this->actingAs($user)->getJson(route('api.analytics'))->assertJsonPath('data.totals.qr_scans', 1);
        Mail::assertQueued(\App\Mail\WelcomeRestaurantOwner::class);
    }

    public function test_request_a_package_then_an_admin_assigns_it_and_the_limits_rise(): void
    {
        Mail::fake();
        config(['services.contact.recipient' => 'owner@qayema.test']);

        $owner = $this->owner(['default_locale' => 'en', 'slug' => 'growing']);
        $pro = Package::findBySlug('pro');

        // Starts on the free package and sees what it allows.
        $this->actingAs($owner->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.package.slug', 'free')
            ->assertJsonPath('data.restaurant.limits.dishes.limit', 40);

        // Asks for Pro. Nothing changes yet — it lands in the admin inbox.
        $this->actingAs($owner->user)->postJson(route('api.packages.request'), [
            'package' => 'pro',
            'message' => 'We are opening a second branch.',
        ])->assertCreated()->assertJsonPath('data.package', 'pro');

        $this->assertDatabaseHas('contact_messages', [
            'user_id' => $owner->user_id,
            'package_id' => $pro->id,
            'message' => 'We are opening a second branch.',
        ]);
        Mail::assertQueued(ContactMessageReceived::class);
        $this->actingAs($owner->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.package.slug', 'free');

        // An admin assigns it from the panel.
        $this->actingAs($this->admin());
        Livewire::test(EditRestaurant::class, ['record' => $owner->id])
            ->fillForm(['package_id' => $pro->id])
            ->call('save')
            ->assertHasNoFormErrors();

        // The owner's allowance moves immediately.
        $this->actingAs($owner->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.package.slug', 'pro')
            ->assertJsonPath('data.restaurant.limits.dishes.limit', 120)
            ->assertJsonPath('data.restaurant.plan.qr_studio', true);
    }

    public function test_a_design_can_be_customised_and_switched_freely(): void
    {
        $owner = $this->owner(['default_locale' => 'en', 'slug' => 'designer']);
        $midnight = Template::factory()
            ->withSettings([['key' => 'primary_color', 'type' => 'color', 'default' => '#ABCDEF']])
            ->create(['slug' => 'midnight', 'sort_order' => 1]);

        // Preview, choose, recolour.
        $this->actingAs($owner->user)->get('/designer?preview='.$midnight->id)->assertOk()->assertSee('--accent: #ABCDEF', false);
        $this->actingAs($owner->user)->postJson(route('api.templates.select'), ['template_id' => $midnight->id])->assertOk();
        $this->actingAs($owner->user)->putJson(route('api.colors-fonts.update'), ['colors' => ['primary_color' => '#112233']])->assertOk();
        $this->get('/designer')->assertOk()->assertSee('--accent: #112233', false);

        // Switch away and back: free both ways, and each design keeps its colours.
        $classic = Template::firstWhere('slug', 'classic');
        $this->actingAs($owner->user)->postJson(route('api.templates.select'), ['template_id' => $classic->id])->assertOk();
        $this->get('/designer')->assertOk()->assertDontSee('--accent: #112233', false);
        $this->actingAs($owner->user)->postJson(route('api.templates.select'), ['template_id' => $midnight->id])->assertOk();
        $this->get('/designer')->assertOk()->assertSee('--accent: #112233', false);
    }

    public function test_flooding_gets_the_ip_banned_and_the_ban_lifts_after_an_hour(): void
    {
        // The admin bypass while banned is covered in BlockedIpTest; switching
        // acting users mid-test trips Filament's session hash check.
        $owner = $this->owner();

        // Hammer a strike-feeding endpoint (uploads: 20/min, then every 429 is
        // a strike; 20 strikes in a minute is a ban).
        for ($i = 0; $i < 45; $i++) {
            $this->actingAs($owner->user)->post(
                route('api.uploads.temp'),
                ['file' => \Illuminate\Http\UploadedFile::fake()->image("f{$i}.jpg", 10, 10), 'context' => 'dish'],
                ['Accept' => 'application/json'],
            );
        }

        $this->assertDatabaseHas('blocked_ips', ['ip' => '127.0.0.1']);
        $this->get('/')->assertForbidden();
        $this->actingAs($owner->user)->getJson(route('api.user'))->assertOk('The API group does not run the ban middleware; the web surface does.');

        $this->travel(61)->minutes();
        \Illuminate\Support\Facades\Cache::flush();
        $this->get('/')->assertOk();
    }

    public function test_lowering_a_package_limit_below_usage_blocks_new_rows_and_raising_it_unblocks(): void
    {
        $owner = $this->owner();
        $category = \App\Models\Category::factory()->create(['restaurant_id' => $owner->id]);
        \App\Models\Dish::factory()->count(5)->create(['restaurant_id' => $owner->id, 'category_id' => $category->id]);
        $free = Package::default();
        $this->actingAs($this->admin());

        Livewire::test(EditPackage::class, ['record' => $free->id])
            ->fillForm(['features.dish_limit' => 3])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->actingAs($owner->user)->postJson(route('api.dishes.store'), ['name' => ['en' => 'x'], 'price' => 1, 'category_id' => $category->id])->assertStatus(422);
        $this->assertSame(5, $owner->dishes()->count());

        $this->actingAs($this->admin());
        Livewire::test(EditPackage::class, ['record' => $free->id])
            ->fillForm(['features.dish_limit' => 10])
            ->call('save')
            ->assertHasNoFormErrors();
        $this->actingAs($owner->user)->postJson(route('api.dishes.store'), ['name' => ['en' => 'x'], 'price' => 1, 'category_id' => $category->id])->assertCreated();
    }

    public function test_deleting_a_restaurant_cascades_content_but_keeps_the_user_and_the_packages(): void
    {
        $owner = $this->ownerOn('pro');
        $category = \App\Models\Category::factory()->create(['restaurant_id' => $owner->id]);
        \App\Models\Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id]);
        $owner->socialLinks()->create(['platform' => 'instagram', 'url' => 'https://instagram.com/x']);
        $owner->featureGrants()->create(['feature' => Feature::DishLimit, 'value' => 5, 'source' => 'admin']);
        $midnight = Template::factory()->create();
        $owner->menuSessions()->create(['session_id' => 's', 'viewed_at' => now()]);
        $userId = $owner->user_id;
        $packageId = $owner->package_id;

        $owner->delete();

        foreach (['categories', 'dishes', 'restaurant_social_links', 'feature_grants', 'menu_sessions'] as $table) {
            $this->assertDatabaseCount($table, 0);
        }
        $this->assertDatabaseHas('users', ['id' => $userId]);
        $this->assertDatabaseHas('packages', ['id' => $packageId]);
        $this->assertDatabaseHas('templates', ['id' => $midnight->id]);
    }

    public function test_password_reset_end_to_end_for_a_google_only_owner(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $user = User::factory()->create(['password' => null]);

        // Can't log in with a password yet.
        $this->post(route('login'), ['email' => $user->email, 'password' => 'anything'])->assertSessionHasErrors('email');

        $this->post(route('password.email'), ['email' => $user->email]);
        $token = null;
        \Illuminate\Support\Facades\Notification::assertSentTo($user, \Illuminate\Auth\Notifications\ResetPassword::class, function ($n) use (&$token) {
            $token = $n->token;

            return true;
        });
        $this->post(route('password.store'), ['token' => $token, 'email' => $user->email, 'password' => 'a-real-password-1', 'password_confirmation' => 'a-real-password-1'])->assertRedirect(route('login'));

        $this->post(route('login'), ['email' => $user->email, 'password' => 'a-real-password-1'])->assertRedirect();
        $this->assertAuthenticatedAs($user);
        $this->actingAs($user->fresh())->getJson(route('api.user'))->assertJsonPath('data.has_password', true);
    }

    public function test_a_reserved_word_can_never_become_a_menu_address(): void
    {
        $user = User::factory()->create(['onboarding_step' => 0, 'onboarding_completed_at' => null]);

        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'Admin Cafe', 'slug' => 'admin'])->assertStatus(422);
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'Admin Cafe', 'slug' => 'admin-cafe'])->assertOk();
    }
}
