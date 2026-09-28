<?php

namespace Tests\Feature\Mail;

use App\Mail\WelcomeRestaurantOwner;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The email an owner gets when onboarding finishes: their menu link, a way
 * back into Qayema and the three steps to go live.
 */
class WelcomeRestaurantOwnerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Restaurant $restaurant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['name' => 'Dani Seif', 'email' => 'dani@qayema.test']);
        $this->restaurant = Restaurant::factory()->create([
            'user_id' => $this->user->id,
            'slug' => 'beit-qayema',
            'name' => ['en' => 'Beit Qayema', 'ar' => 'بيت قائمة'],
        ]);
    }

    public function test_it_is_queued_with_a_fixed_subject_and_the_html_view(): void
    {
        $mail = new WelcomeRestaurantOwner($this->user, $this->restaurant);

        $this->assertInstanceOf(ShouldQueue::class, $mail);
        $mail->assertHasSubject('Your menu is live, welcome to Qayema!');
        $this->assertSame('emails.welcome-restaurant-owner', $mail->content()->view);
    }

    public function test_it_greets_the_owner_and_links_to_their_menu(): void
    {
        $mail = new WelcomeRestaurantOwner($this->user, $this->restaurant);

        $mail->assertSeeInHtml('Hi Dani Seif, Beit Qayema is set up on Qayema.', false);
        $mail->assertSeeInHtml('href="'.url('/beit-qayema').'"', false);
        $mail->assertSeeInHtml(parse_url(config('app.url'), PHP_URL_HOST).'/beit-qayema', false);
        $mail->assertSeeInHtml('href="'.config('app.dashboard_url').'"', false);
        $mail->assertDontSeeInHtml('AI scanner', false);
        $mail->assertDontSeeInHtml('Sushi By Ahmad', false);
        $mail->assertSeeInHtml('href="'.route('contact').'"', false);
        $mail->assertSeeInHtml('href="'.route('privacy').'"', false);
        $mail->assertSeeInHtml('href="'.route('terms').'"', false);
        $mail->assertSeeInHtml(asset('images/logo/q-logo.png'), false);
        $mail->assertSeeInOrderInHtml(['Build your menu', 'Print your QR code', 'Go live &amp; share'], false);
        $mail->assertSeeInHtml('you created a Qayema account for Beit Qayema', false);
        $mail->assertSeeInHtml('&copy; '.date('Y').' Lebify Group', false);
    }

    public function test_both_menu_links_point_at_the_restaurant_slug(): void
    {
        $html = (new WelcomeRestaurantOwner($this->user, $this->restaurant))->render();

        $this->assertSame(2, substr_count($html, 'href="'.url('/beit-qayema').'"'));
    }

    public function test_the_owners_name_is_escaped(): void
    {
        $this->user->update(['name' => '<script>alert(1)</script>']);

        $html = (new WelcomeRestaurantOwner($this->user, $this->restaurant))->render();

        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function test_rendered_in_arabic_it_names_the_restaurant_in_arabic(): void
    {
        $mail = (new WelcomeRestaurantOwner($this->user, $this->restaurant))->locale('ar');

        $html = $mail->render();

        $this->assertStringContainsString('Hi Dani Seif, بيت قائمة is set up on Qayema.', $html);
        $this->assertStringNotContainsString('Beit Qayema', $html);
        // The copy itself is English-only, and the link does not change.
        $this->assertStringContainsString('Three steps to service', $html);
        $this->assertStringContainsString('href="'.url('/beit-qayema').'"', $html);
        $this->assertSame('en', app()->getLocale(), 'Rendering in a locale must not leak it into the app.');
    }

    public function test_by_default_it_renders_in_english(): void
    {
        $html = (new WelcomeRestaurantOwner($this->user, $this->restaurant))->render();

        $this->assertStringContainsString('Hi Dani Seif, Beit Qayema is set up on Qayema.', $html);
        $this->assertStringNotContainsString('بيت قائمة', $html);
    }

    public function test_finishing_onboarding_queues_it_to_the_owner(): void
    {
        Mail::fake();
        $this->user->update(['onboarding_step' => 2, 'onboarding_completed_at' => null]);

        $this->actingAs($this->user)->postJson(route('onboarding.advance'), [
            '_step' => 3,
            'logo_key' => '11111111-1111-1111-1111-111111111111',
        ])->assertOk()->assertJson(['completed' => true]);

        Mail::assertQueued(WelcomeRestaurantOwner::class, function (WelcomeRestaurantOwner $mail): bool {
            return $mail->hasTo('dani@qayema.test')
                && $mail->user->is($this->user)
                && $mail->restaurant->is($this->restaurant);
        });
        Mail::assertNotSent(WelcomeRestaurantOwner::class);
    }

    public function test_a_rejected_final_step_does_not_send_it(): void
    {
        Mail::fake();
        $this->user->update(['onboarding_step' => 2, 'onboarding_completed_at' => null]);

        $this->actingAs($this->user)
            ->postJson(route('onboarding.advance'), ['_step' => 3])
            ->assertStatus(422);

        Mail::assertNothingQueued();
        $this->assertNull($this->user->fresh()->onboarding_completed_at);
    }
}
