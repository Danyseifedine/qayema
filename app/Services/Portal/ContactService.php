<?php

namespace App\Services\Portal;

use App\Exceptions\TooManyContactMessages;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;

class ContactService
{
    private const MAX_PER_DAY = 3;

    // The value 86400 refers to the number of seconds in 24 hours (1 day).
    private const DECAY_SECONDS = 86400;

    public function isThrottled(string $ip): bool
    {
        return RateLimiter::tooManyAttempts($this->key($ip), self::MAX_PER_DAY);
    }

    public function dailyLimit(): int
    {
        return self::MAX_PER_DAY;
    }

    public function availableInHours(string $ip): int
    {
        return (int) ceil(RateLimiter::availableIn($this->key($ip)) / 3600);
    }

    /**
     * Submit a contact message: persist it and notify the recipient — self-guarded
     * by the daily limit. RateLimiter::attempt() checks and records the hit
     * atomically, so concurrent requests can't slip past the limit.
     *
     * @param  array{name: string, email: string, message: string}  $data
     *
     * @throws TooManyContactMessages when the per-IP daily limit is reached
     */
    public function submit(array $data, string $ip): ContactMessage
    {
        $contact = RateLimiter::attempt(
            $this->key($ip),
            self::MAX_PER_DAY,
            fn (): ContactMessage => $this->createAndNotify($data, $ip),
            self::DECAY_SECONDS,
        );

        if ($contact === false) {
            throw new TooManyContactMessages($this->availableInHours($ip));
        }

        return $contact;
    }

    /**
     * @param  array{name: string, email: string, message: string}  $data
     */
    private function createAndNotify(array $data, string $ip): ContactMessage
    {
        $contact = ContactMessage::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'message' => $data['message'],
            'ip_address' => $ip,
        ]);

        // The mailable is ShouldQueue, so this only pushes a job (no SMTP here).
        // Guard the dispatch anyway: the message is already saved, so a queue
        // hiccup must not fail the submission — just record it.
        try {
            Mail::to(config('services.contact.recipient'))->send(new ContactMessageReceived($contact));
        } catch (\Throwable $e) {
            Log::error('Failed to queue contact notification email.', [
                'contact_message_id' => $contact->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $contact;
    }

    private function key(string $ip): string
    {
        return 'contact:'.$ip;
    }
}
