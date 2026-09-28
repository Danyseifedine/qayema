<?php

namespace Tests\Integration\Services\Contact;

use App\Exceptions\TooManyContactMessages;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Package;
use App\Services\Contact\ContactService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * ContactService called directly: storing, notifying, and the durable per-IP
 * quota of three a day.
 *
 * The countdown in availableInHours() reads PHP's own clock, not Carbon's, so
 * these tests move the rows in time rather than the clock.
 */
class ContactServiceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const IP = '203.0.113.10';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.contact.recipient' => 'team@qayema.test']);
    }

    private function service(): ContactService
    {
        return app(ContactService::class);
    }

    /**
     * @return array{name: string, email: string, message: string}
     */
    private function data(array $overrides = []): array
    {
        return ['name' => 'Rana', 'email' => 'rana@example.com', 'message' => 'Hello there', ...$overrides];
    }

    /** A message already sent from the IP some seconds ago. */
    private function sentAgo(int $seconds, string $ip = self::IP): ContactMessage
    {
        return ContactMessage::factory()->create(['ip_address' => $ip, 'created_at' => now()->subSeconds($seconds)]);
    }

    public function test_a_plain_message_is_stored_and_the_team_is_told(): void
    {
        Mail::fake();

        $contact = $this->service()->submit($this->data(), self::IP);

        $this->assertSame(
            ['name' => 'Rana', 'email' => 'rana@example.com', 'message' => 'Hello there', 'ip_address' => self::IP, 'user_id' => null, 'package_id' => null],
            $contact->fresh()->only('name', 'email', 'message', 'ip_address', 'user_id', 'package_id'),
        );
        Mail::assertQueued(ContactMessageReceived::class, fn (ContactMessageReceived $mail): bool => $mail->hasTo('team@qayema.test'));
        Mail::assertQueuedCount(1);
    }

    public function test_a_package_request_carries_the_owner_and_the_package(): void
    {
        Mail::fake();
        $owner = $this->owner();
        $premium = Package::findBySlug('premium');

        $contact = $this->service()->submit($this->data(['user_id' => $owner->user_id, 'package_id' => $premium->id]), self::IP);

        $this->assertSame($owner->user_id, $contact->fresh()->user_id);
        $this->assertSame($premium->id, $contact->fresh()->package_id);
        Mail::assertQueued(ContactMessageReceived::class);
    }

    public function test_no_recipient_means_a_warning_and_no_mail(): void
    {
        Mail::fake();
        Log::spy();
        config(['services.contact.recipient' => '']);

        $contact = $this->service()->submit($this->data(), self::IP);

        $this->assertDatabaseHas('contact_messages', ['id' => $contact->id]);
        Mail::assertNothingQueued();
        Log::shouldHaveReceived('warning')->once()->withArgs(fn (string $message, array $context): bool => $context === ['id' => $contact->id]);
    }

    public function test_a_mail_failure_is_logged_and_the_message_still_stands(): void
    {
        Log::spy();
        Mail::shouldReceive('to')->once()->andThrow(new RuntimeException('queue is down'));

        $contact = $this->service()->submit($this->data(), self::IP);

        $this->assertDatabaseHas('contact_messages', ['id' => $contact->id, 'ip_address' => self::IP]);
        Log::shouldHaveReceived('error')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'Failed to queue contact notification email.'
                && $context === ['contact_message_id' => $contact->id, 'error' => 'queue is down'],
        );
    }

    public function test_an_ip_with_nothing_sent_waits_for_nothing(): void
    {
        $this->assertFalse($this->service()->isThrottled(self::IP));
        $this->assertSame(0, $this->service()->availableInHours(self::IP));
    }

    public function test_the_fourth_in_a_day_is_refused_with_the_hours_left(): void
    {
        Mail::fake();
        $this->sentAgo(2 * 3600 + 60);
        $this->sentAgo(3600);
        $this->assertFalse($this->service()->isThrottled(self::IP));

        $this->service()->submit($this->data(), self::IP);

        $this->assertTrue($this->service()->isThrottled(self::IP));

        try {
            $this->service()->submit($this->data(), self::IP);
            $this->fail('The fourth message should have been refused.');
        } catch (TooManyContactMessages $exception) {
            // The oldest went 2h01m ago, so the slot frees in 21h59m: 22 hours.
            $this->assertSame(22, $exception->retryAfterHours);
            $this->assertSame('Too many contact messages from this address.', $exception->getMessage());
        }

        $this->assertSame(3, ContactMessage::query()->where('ip_address', self::IP)->count());
        Mail::assertQueuedCount(1);
    }

    public function test_the_wait_is_never_less_than_an_hour(): void
    {
        $this->sentAgo(86400 - 30);
        $this->sentAgo(60);
        $this->sentAgo(30);

        $this->assertTrue($this->service()->isThrottled(self::IP));
        $this->assertSame(1, $this->service()->availableInHours(self::IP));
    }

    public function test_a_message_a_day_old_no_longer_counts(): void
    {
        Mail::fake();
        $this->sentAgo(86400 + 5);
        $this->sentAgo(600);
        $this->sentAgo(300);

        $this->assertFalse($this->service()->isThrottled(self::IP));
        // The countdown runs from the oldest message still inside the day.
        $this->assertSame(24, $this->service()->availableInHours(self::IP));

        $contact = $this->service()->submit($this->data(), self::IP);

        $this->assertDatabaseHas('contact_messages', ['id' => $contact->id]);
        $this->assertSame(4, ContactMessage::query()->where('ip_address', self::IP)->count());
        $this->assertTrue($this->service()->isThrottled(self::IP), 'The new one fills the day again.');
    }

    public function test_the_day_ends_exactly_twenty_four_hours_after_the_oldest(): void
    {
        $this->freezeSecond();
        $this->sentAgo(86400);
        $this->sentAgo(3600);
        $this->sentAgo(60);

        $this->assertTrue($this->service()->isThrottled(self::IP), 'Exactly a day old still counts.');

        $this->travel(1)->second();

        $this->assertFalse($this->service()->isThrottled(self::IP));
    }

    public function test_the_quota_is_per_ip_and_shared_by_both_kinds_of_message(): void
    {
        Mail::fake();
        $this->sentAgo(60);
        ContactMessage::factory()->packageRequest()->create(['ip_address' => self::IP, 'created_at' => now()->subMinute()]);
        $this->sentAgo(60);
        $this->sentAgo(60, '198.51.100.1');

        $this->assertTrue($this->service()->isThrottled(self::IP));
        $this->assertFalse($this->service()->isThrottled('198.51.100.1'));

        $this->expectException(TooManyContactMessages::class);

        $this->service()->submit($this->data(['user_id' => $this->owner()->user_id, 'package_id' => Package::findBySlug('pro')->id]), self::IP);
    }

    public function test_a_trusted_ip_is_never_throttled(): void
    {
        Mail::fake();
        config(['security.trusted_ips' => [self::IP]]);
        $this->sentAgo(60);
        $this->sentAgo(60);
        $this->sentAgo(60);

        $this->assertFalse($this->service()->isThrottled(self::IP));

        $contact = $this->service()->submit($this->data(), self::IP);

        $this->assertDatabaseHas('contact_messages', ['id' => $contact->id]);
    }
}
