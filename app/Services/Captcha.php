<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class Captcha
{
    private const VERIFY_ENDPOINT = 'https://www.google.com/recaptcha/api/siteverify';

    public function verify(?string $token, ?string $ip = null): bool
    {
        if (! config('services.recaptcha.enabled')) {
            return true;
        }

        if (blank($token)) {
            return false;
        }

        $payload = [
            'secret' => config('services.recaptcha.secret'),
            'response' => $token,
        ];

        if (filled($ip)) {
            $payload['remoteip'] = $ip;
        }

        try {
            $response = Http::asForm()->post(self::VERIFY_ENDPOINT, $payload);
        } catch (\Throwable) {
            return false;
        }

        return $response->successful() && (bool) $response->json('success', false);
    }
}
