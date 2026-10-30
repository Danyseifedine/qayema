<?php

namespace App\Mail;

use App\Models\PosConnection;
use App\Models\Restaurant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells an owner their menu has not reached their POS for over an hour, so
 * dishes and orders are not updating, with what went wrong last.
 */
class PosUnreachable extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Restaurant $restaurant,
        public PosConnection $link,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your menu cannot reach your POS');
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.pos-unreachable', with: [
            'dashboardUrl' => rtrim((string) config('app.dashboard_url'), '/').'/features',
        ]);
    }
}
