<?php

namespace Tests\Feature\Portal;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The honeypot on the contact form, as the page's JavaScript submits it
 * (JSON). A bot must get the very answer a person would, in either language.
 */
class ContactHoneypotTest extends TestCase
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

    public function test_a_filled_honeypot_over_json_looks_sent_but_stores_nothing(): void
    {
        $this->withSession(['owner_locale' => 'en'])
            ->postJson(route('contact.store'), $this->payload(['hp_field' => 'I am a bot']))
            ->assertOk()
            ->assertExactJson(['message' => 'Your message has been sent.']);

        $this->assertDatabaseCount('contact_messages', 0);
        Mail::assertNothingOutgoing();
    }

    public function test_the_bot_gets_the_same_answer_a_person_does(): void
    {
        $person = $this->postJson(route('contact.store'), $this->payload())->assertOk()->json();
        $bot = $this->postJson(route('contact.store'), $this->payload(['hp_field' => 'x', 'email' => 'bot@example.com']))->assertOk()->json();

        $this->assertSame($person, $bot);
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_the_answer_is_in_the_visitors_language(): void
    {
        $this->withSession(['owner_locale' => 'ar'])
            ->postJson(route('contact.store'), $this->payload(['hp_field' => 'I am a bot']))
            ->assertOk()
            ->assertExactJson(['message' => 'تم إرسال رسالتك.']);
    }

    /** The honeypot never spends the per-IP daily quota. */
    public function test_bot_submissions_do_not_use_up_the_quota(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('contact.store'), $this->payload(['hp_field' => 'x']))->assertOk();
        }

        $this->postJson(route('contact.store'), $this->payload())->assertOk();
        $this->assertDatabaseCount('contact_messages', 1);
    }

    /** Validation still runs first: a bot that also sends junk gets a 422. */
    public function test_an_invalid_body_is_refused_before_the_honeypot(): void
    {
        $this->postJson(route('contact.store'), ['hp_field' => 'x'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'message']);
    }
}
