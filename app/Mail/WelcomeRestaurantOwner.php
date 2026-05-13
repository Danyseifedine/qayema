<?php

namespace App\Mail;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeRestaurantOwner extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Restaurant $restaurant,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your menu is live, welcome to Qayema!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.welcome-restaurant-owner',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
