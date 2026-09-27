<?php

namespace Tests\Unit\Services\Security;

use App\Services\Security\Captcha;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaptchaTest extends TestCase
{
    private function enabled(): void
    {
        config(['services.recaptcha.enabled' => true, 'services.recaptcha.secret' => 'secret-key']);
    }

    public function test_when_disabled_everything_passes_without_a_request(): void
    {
        config(['services.recaptcha.enabled' => false]);
        Http::fake();

        $this->assertTrue(app(Captcha::class)->verify(null));
        $this->assertTrue(app(Captcha::class)->verify('anything'));
        Http::assertNothingSent();
    }

    public function test_when_enabled_a_missing_token_fails_without_a_request(): void
    {
        $this->enabled();
        Http::fake();

        $this->assertFalse(app(Captcha::class)->verify(null));
        $this->assertFalse(app(Captcha::class)->verify(''));
        Http::assertNothingSent();
    }

    public function test_google_saying_yes_passes(): void
    {
        $this->enabled();
        Http::fake(['www.google.com/recaptcha/*' => Http::response(['success' => true, 'score' => 0.9])]);

        $this->assertTrue(app(Captcha::class)->verify('token', '203.0.113.9'));

        Http::assertSent(fn ($request) => $request['secret'] === 'secret-key'
            && $request['response'] === 'token'
            && $request['remoteip'] === '203.0.113.9');
    }

    public function test_google_saying_no_fails(): void
    {
        $this->enabled();
        Http::fake(['www.google.com/recaptcha/*' => Http::response(['success' => false, 'error-codes' => ['invalid-input-response']])]);

        $this->assertFalse(app(Captcha::class)->verify('token'));
    }

    public function test_a_google_outage_fails_closed(): void
    {
        $this->enabled();
        Http::fake(['www.google.com/recaptcha/*' => Http::response('', 503)]);

        $this->assertFalse(app(Captcha::class)->verify('token'));
    }

    public function test_a_network_error_fails_closed(): void
    {
        $this->enabled();
        Http::fake(fn () => throw new ConnectionException('timeout'));

        $this->assertFalse(app(Captcha::class)->verify('token'));
    }

    public function test_the_ip_is_only_forwarded_when_known(): void
    {
        $this->enabled();
        Http::fake(['www.google.com/recaptcha/*' => Http::response(['success' => true])]);

        app(Captcha::class)->verify('token', null);

        Http::assertSent(fn ($request) => ! isset($request['remoteip']));
    }
}
