<?php

namespace Tests\Feature\Portal;

use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['services.contact.recipient' => 'inbox@example.test', 'services.recaptcha.enabled' => false]);
    }

    /** @return array<string, string> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Rami',
            'email' => 'rami@example.com',
            'message' => 'Hello, I would like to know more about Qayema.',
        ], $overrides);
    }

    public function test_the_contact_page_renders(): void
    {
        $this->get(route('contact'))->assertOk()->assertSee(route('contact.store'));
    }

    public function test_a_valid_message_is_stored_and_the_team_is_notified(): void
    {
        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHas('success', true);

        $this->assertDatabaseHas('contact_messages', ['email' => 'rami@example.com', 'ip_address' => '127.0.0.1']);
        Mail::assertQueued(ContactMessageReceived::class, fn ($mail) => $mail->hasTo('inbox@example.test'));
    }

    public function test_the_ajax_path_answers_json(): void
    {
        $this->postJson(route('contact.store'), $this->payload())
            ->assertOk()
            ->assertJsonStructure(['message']);

        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_every_rule_is_enforced(): void
    {
        $cases = [
            'name missing' => ['name' => ''],
            'name too long' => ['name' => str_repeat('x', 101)],
            'email missing' => ['email' => ''],
            'email invalid' => ['email' => 'not-an-email'],
            'message missing' => ['message' => ''],
            'message too short' => ['message' => 'short'],
            'message too long' => ['message' => str_repeat('x', 2001)],
        ];

        foreach ($cases as $label => $bad) {
            $this->postJson(route('contact.store'), $this->payload($bad))
                ->assertStatus(422, $label);
        }

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_a_filled_honeypot_is_silently_dropped(): void
    {
        $this->post(route('contact.store'), $this->payload(['hp_field' => 'I am a bot']))
            ->assertRedirect()
            ->assertSessionHas('success', true);

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingQueued();
    }

    public function test_the_fourth_message_from_one_ip_in_a_day_is_refused_with_a_wait(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson(route('contact.store'), $this->payload(['email' => "u{$i}@example.com"]))->assertOk();
        }

        $this->postJson(route('contact.store'), $this->payload())
            ->assertStatus(429)
            ->assertJsonPath('code', 'too_many_requests')
            ->assertJsonStructure(['errors' => ['rate_limit']]);

        $this->post(route('contact.store'), $this->payload())
            ->assertRedirect()
            ->assertSessionHasErrors('rate_limit');

        $this->assertDatabaseCount('contact_messages', 3);
    }

    public function test_the_daily_window_rolls_over(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson(route('contact.store'), $this->payload())->assertOk();
        }

        $this->travel(25)->hours();

        $this->postJson(route('contact.store'), $this->payload())->assertOk();
    }

    public function test_a_different_ip_has_its_own_quota(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->postJson(route('contact.store'), $this->payload())->assertOk();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.5'])
            ->postJson(route('contact.store'), $this->payload())
            ->assertOk();
    }

    public function test_captcha_is_enforced_when_enabled(): void
    {
        config(['services.recaptcha.enabled' => true, 'services.recaptcha.secret' => 's']);

        $this->postJson(route('contact.store'), $this->payload())
            ->assertStatus(422)
            ->assertJsonValidationErrors('g-recaptcha-response');
    }

    public function test_the_message_is_stored_raw_and_never_executed(): void
    {
        $this->postJson(route('contact.store'), $this->payload(['message' => '<script>alert(1)</script> and more text here']))->assertOk();

        $this->assertSame('<script>alert(1)</script> and more text here', ContactMessage::first()->message);
    }

    public function test_a_missing_recipient_is_logged_rather_than_crashing(): void
    {
        config(['services.contact.recipient' => null]);
        Log::spy();

        $this->postJson(route('contact.store'), $this->payload())->assertOk();

        $this->assertDatabaseCount('contact_messages', 1);
        Mail::assertNothingQueued();
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_the_endpoint_is_rate_limited_per_ip(): void
    {
        $status = null;
        for ($i = 0; $i < 15 && $status !== 429; $i++) {
            $status = $this->post(route('contact.store'), ['name' => ''])->getStatusCode();
        }

        $this->assertSame(429, $status);
    }
}
