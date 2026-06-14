<?php

namespace App\Services\Portal;

use App\Exceptions\TooManyContactMessages;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ContactService
{
    private const MAX_PER_DAY = 3;

    public function isThrottled(string $ip): bool
    {
        return $this->recentMessages($ip)->count() >= self::MAX_PER_DAY;
    }

    public function dailyLimit(): int
    {
        return self::MAX_PER_DAY;
    }

    public function availableInHours(string $ip): int
    {
        $oldest = $this->recentMessages($ip)->min('created_at');

        if ($oldest === null) {
            return 0;
        }

        $availableAt = Carbon::parse($oldest)->addDay();
        $secondsLeft = max(0, $availableAt->getTimestamp() - time());

        return max(1, (int) ceil($secondsLeft / 3600));
    }

    /**
     * Submit a contact message: persist it and notify the recipient, guarded by a
     * durable per-IP daily limit. The quota is enforced against the contact_messages
     * table itself — not volatile cache — so it cannot be reset by a deploy, a queue
     * restart, or `cache:clear`/`optimize:clear`.
     *
     * @param  array{name: string, email: string, message: string}  $data
     *
     * @throws TooManyContactMessages when the per-IP daily limit is reached
     */
    public function submit(array $data, string $ip): ContactMessage
    {
        if ($this->isThrottled($ip)) {
            throw new TooManyContactMessages($this->availableInHours($ip));
        }

        return $this->createAndNotify($data, $ip);
    }

    /**
     * Messages from this IP within the trailing 24 hours — the rows the daily quota counts.
     *
     * @return Builder<ContactMessage>
     */
    private function recentMessages(string $ip): Builder
    {
        return ContactMessage::query()
            ->where('ip_address', $ip)
            ->where('created_at', '>=', now()->subDay());
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
}
