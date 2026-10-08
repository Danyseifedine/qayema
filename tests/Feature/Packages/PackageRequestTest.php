<?php

namespace Tests\Feature\Packages;

use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\DeviceToken;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\Support\CreatesOwners;
use Tests\Support\FakesFirebase;
use Tests\TestCase;

/**
 * Asking to move to a paid package. Nothing is charged and nothing changes on
 * the restaurant: the request lands in the admin inbox and a human assigns it.
 */
class PackageRequestTest extends TestCase
{
    use CreatesOwners, FakesFirebase, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['services.contact.recipient' => 'owner@qayema.test']);
    }

    public function test_it_requires_authentication(): void
    {
        $this->postJson(route('api.packages.request'), ['package' => 'pro'])->assertUnauthorized();
    }

    public function test_a_request_is_stored_against_the_owner_and_the_package(): void
    {
        $owner = $this->owner();
        $pro = Package::findBySlug('pro');

        $this->actingAs($owner->user)
            ->postJson(route('api.packages.request'), ['package' => 'pro', 'message' => 'We need more room.'])
            ->assertNoContent(201);

        $contact = ContactMessage::firstOrFail();
        $this->assertSame($owner->user_id, $contact->user_id);
        $this->assertSame($pro->id, $contact->package_id);
        $this->assertSame('We need more room.', $contact->message);
        $this->assertSame($owner->user->email, $contact->email);
        $this->assertTrue($contact->isPackageRequest());
    }

    public function test_the_package_the_owner_is_on_does_not_change(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->postJson(route('api.packages.request'), ['package' => 'premium'])->assertCreated();

        $this->assertSame('free', $owner->fresh()->effectivePackage()->slug);
        $this->assertSame(40, $owner->fresh()->dish_limit);
    }

    public function test_the_admin_recipient_is_emailed_with_the_package(): void
    {
        $owner = $this->owner();
        $pro = Package::findBySlug('pro');

        $this->actingAs($owner->user)->postJson(route('api.packages.request'), ['package' => 'pro'])->assertCreated();

        Mail::assertQueued(ContactMessageReceived::class, function (ContactMessageReceived $mail) use ($pro): bool {
            return $mail->hasTo('owner@qayema.test')
                && $mail->contactMessage->package_id === $pro->id
                && str_contains($mail->envelope()->subject, 'Package request: Pro');
        });
    }

    public function test_the_admins_phones_are_told(): void
    {
        $this->fakeFirebase();
        $phone = DeviceToken::factory()->for($this->admin())->create();
        $owner = $this->owner(['name' => ['en' => 'Beit Rami']]);

        $this->actingAs($owner->user)->postJson(route('api.packages.request'), ['package' => 'pro'])->assertCreated();

        $this->assertSame([$phone->token], $this->pushes[0]['tokens']);
        $this->assertSame('Package request: Pro', $this->pushes[0]['message']['notification']['title']);
        $this->assertSame((string) $owner->id, $this->pushes[0]['message']['data']['restaurant_id']);
    }

    public function test_an_empty_message_gets_a_default_body(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->postJson(route('api.packages.request'), ['package' => 'custom'])->assertCreated();

        $this->assertSame('Package request: Custom', ContactMessage::firstOrFail()->message);
    }

    public function test_the_default_package_cannot_be_requested(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.packages.request'), ['package' => 'free'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('package');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_an_unknown_package_is_rejected(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.packages.request'), ['package' => 'platinum'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('package');
    }

    public function test_an_over_long_message_is_rejected(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.packages.request'), ['package' => 'pro', 'message' => str_repeat('a', 2001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');
    }

    public function test_the_fourth_request_in_a_day_is_refused_with_a_retry_hint(): void
    {
        $owner = $this->owner();

        for ($i = 0; $i < 3; $i++) {
            $this->actingAs($owner->user)->postJson(route('api.packages.request'), ['package' => 'pro'])->assertCreated();
        }

        $response = $this->actingAs($owner->user)
            ->postJson(route('api.packages.request'), ['package' => 'pro'])
            ->assertStatus(429)
            ->assertJsonPath('code', 'too_many_requests');

        $this->assertGreaterThan(0, $response->json('retry_after'));
        $this->assertDatabaseCount('contact_messages', 3);
    }

    public function test_a_missing_recipient_is_logged_and_the_request_still_succeeds(): void
    {
        config(['services.contact.recipient' => null]);
        Log::spy();
        $owner = $this->owner();

        $this->actingAs($owner->user)->postJson(route('api.packages.request'), ['package' => 'pro'])->assertCreated();

        $this->assertDatabaseCount('contact_messages', 1);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_the_email_body_names_the_package_and_links_to_the_restaurant(): void
    {
        $owner = $this->owner(['slug' => 'beit-qayema']);

        $this->actingAs($owner->user)->postJson(route('api.packages.request'), ['package' => 'pro'])->assertCreated();

        // Mail::fake() never renders the view, so render it here: a broken
        // template would otherwise only show up in a real inbox.
        $rendered = (new ContactMessageReceived(ContactMessage::firstOrFail()))->render();

        $this->assertStringContainsString('Package request', $rendered);
        $this->assertStringContainsString('Pro', $rendered);
        $this->assertStringContainsString(
            RestaurantResource::getUrl('edit', ['record' => $owner]),
            $rendered,
            'The admin can open the restaurant straight from the email.',
        );
    }

    public function test_a_public_enquiry_email_still_renders_without_a_package(): void
    {
        $contact = ContactMessage::create([
            'name' => 'A guest',
            'email' => 'guest@example.test',
            'message' => 'Do you support **Arabic** and `code`?',
            'ip_address' => '127.0.0.1',
        ]);

        $rendered = (new ContactMessageReceived($contact))->render();

        $this->assertStringContainsString('New message from A guest', $rendered);
        $this->assertStringNotContainsString('Requested package', $rendered);
        // Markdown characters in the body are escaped, so a message cannot
        // style the email or smuggle a link into it.
        $this->assertStringNotContainsString('<strong>Arabic</strong>', $rendered);
        $this->assertStringNotContainsString('<code>code</code>', $rendered);
    }

    public function test_an_owner_without_an_email_may_ask_and_the_admin_sees_their_username(): void
    {
        $owner = $this->owner();
        $owner->user->forceFill(['email' => null, 'username' => 'beit.rami'])->save();

        $this->actingAs($owner->user)->postJson(route('api.packages.request'), ['package' => 'pro'])->assertCreated();

        $contact = ContactMessage::firstOrFail();
        $this->assertNull($contact->email);

        $mail = new ContactMessageReceived($contact);
        $this->assertSame([], $mail->envelope()->replyTo);
        $rendered = $mail->render();
        $this->assertStringContainsString('username beit.rami, no email', $rendered);
        $this->assertStringNotContainsString('mailto:', $rendered);
    }

    public function test_a_user_without_a_restaurant_may_still_ask(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.packages.request'), ['package' => 'pro'])->assertCreated();

        $this->assertSame($user->id, ContactMessage::firstOrFail()->user_id);
    }

    public function test_a_package_no_longer_offered_cannot_be_asked_for(): void
    {
        Package::query()->where('slug', 'pro')->firstOrFail()->update(['is_active' => false]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.packages.request'), ['package' => 'pro'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['package' => 'That package cannot be requested.']);

        $this->assertSame(0, ContactMessage::query()->count());
    }
}
