<?php

namespace App\Mail;

use App\Models\ContactMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ContactMessage $contactMessage) {}

    public function envelope(): Envelope
    {
        $package = $this->contactMessage->package;

        $subject = $package === null
            ? 'New contact message from '.$this->contactMessage->name
            : 'Package request: '.$package->getTranslation('name', 'en').' from '.$this->contactMessage->name;

        // An owner who signed up with a username has no address to answer.
        return new Envelope(
            replyTo: filled($this->contactMessage->email)
                ? [new Address($this->contactMessage->email, $this->contactMessage->name)]
                : [],
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-received',
        );
    }
}
