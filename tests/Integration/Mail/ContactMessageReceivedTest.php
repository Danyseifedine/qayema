<?php

namespace Tests\Integration\Mail;

use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin's copy of a contact-form message or a package request: who wrote,
 * what they asked for, and a one-click reply.
 */
class ContactMessageReceivedTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_plain_message_is_titled_after_the_sender_and_replies_to_them(): void
    {
        $contact = ContactMessage::factory()->create([
            'name' => 'Rima Haddad',
            'email' => 'rima@example.test',
            'message' => 'Do you print the QR cards for us?',
        ]);

        $mail = new ContactMessageReceived($contact);

        $this->assertInstanceOf(ShouldQueue::class, $mail);
        $mail->assertHasSubject('New contact message from Rima Haddad');
        $mail->assertHasReplyTo('rima@example.test', 'Rima Haddad');
        $this->assertSame('emails.contact-received', $mail->content()->markdown);
        $this->assertSame([], $mail->attachments());

        $html = $mail->render();
        $this->assertStringContainsString('New message from Rima Haddad', $html);
        $this->assertStringContainsString('rima@example.test', $html);
        $this->assertStringContainsString('Do you print the QR cards for us?', $html);
        $this->assertStringContainsString('href="mailto:rima@example.test"', $html);
        $this->assertStringContainsString('Reply to Rima Haddad', $html);
        $this->assertStringNotContainsString('Requested package', $html);
        $this->assertStringNotContainsString('in admin', $html, 'A guest has no restaurant to open.');
    }

    public function test_a_package_request_names_the_package_in_subject_and_body(): void
    {
        $premium = Package::findBySlug('premium');
        $user = User::factory()->create();
        Restaurant::factory()->create(['user_id' => $user->id, 'slug' => 'beit', 'name' => ['en' => 'Beit Qayema']]);
        $contact = ContactMessage::factory()->packageRequest($premium)->create([
            'user_id' => $user->id,
            'name' => 'Dani',
            'email' => 'dani@qayema.test',
        ]);

        $mail = new ContactMessageReceived($contact);

        $mail->assertHasSubject('Package request: '.$premium->getTranslation('name', 'en').' — Dani');
        $mail->assertHasReplyTo('dani@qayema.test', 'Dani');

        $html = $mail->render();
        $this->assertStringContainsString('Package request from Dani', $html);
        $this->assertStringContainsString('Requested package:', $html);
        $this->assertStringContainsString($premium->getTranslation('name', 'en'), $html);
        $this->assertStringContainsString('Open Beit Qayema in admin', $html);
        $this->assertStringContainsString(
            RestaurantResource::getUrl('edit', ['record' => $user->restaurant]),
            $html,
        );
    }

    public function test_the_package_name_is_english_even_in_an_arabic_request(): void
    {
        app()->setLocale('ar');
        $pro = Package::findBySlug('pro');
        $contact = ContactMessage::factory()->packageRequest($pro)->create(['name' => 'Dani']);

        (new ContactMessageReceived($contact))
            ->assertHasSubject('Package request: '.$pro->getTranslation('name', 'en').' — Dani');
    }

    public function test_a_restaurant_without_an_english_name_is_named_by_its_slug(): void
    {
        $user = User::factory()->create();
        Restaurant::factory()->create(['user_id' => $user->id, 'slug' => 'only-arabic', 'name' => ['ar' => 'مطعم']]);
        $contact = ContactMessage::factory()->create(['user_id' => $user->id, 'name' => 'Owner']);

        $html = (new ContactMessageReceived($contact))->render();

        $this->assertStringContainsString('Open only-arabic in admin', $html);
    }

    public function test_the_message_cannot_inject_markdown_or_html(): void
    {
        $contact = ContactMessage::factory()->create([
            'message' => '# Heading [click](https://evil.test) <b>bold</b>',
        ]);

        $html = (new ContactMessageReceived($contact))->render();

        $this->assertStringContainsString('Heading', $html);
        $this->assertStringContainsString('https://evil.test', $html, 'The text is kept, only not as a link.');
        $this->assertStringNotContainsString('href="https://evil.test"', $html);
        $this->assertStringNotContainsString('<b>bold</b>', $html);
        $this->assertStringNotContainsString('<h1>Heading', $html);
    }
}
