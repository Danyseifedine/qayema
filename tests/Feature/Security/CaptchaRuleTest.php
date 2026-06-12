<?php

namespace Tests\Feature\Security;

use App\Rules\ValidCaptcha;
use App\Services\Captcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class CaptchaRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_recaptcha_is_disabled_by_default_in_tests(): void
    {
        $this->assertFalse((bool) config('services.recaptcha.enabled'));
    }

    public function test_captcha_service_returns_true_when_disabled(): void
    {
        $captcha = app(Captcha::class);

        $this->assertTrue($captcha->verify(null));
        $this->assertTrue($captcha->verify('any-token'));
    }

    public function test_valid_captcha_rule_passes_when_disabled(): void
    {
        $validator = Validator::make(
            ['g-recaptcha-response' => null],
            ['g-recaptcha-response' => [new ValidCaptcha]],
        );

        $this->assertTrue($validator->passes());
    }

    public function test_valid_captcha_rule_passes_with_a_token_when_disabled(): void
    {
        $validator = Validator::make(
            ['g-recaptcha-response' => 'some-token'],
            ['g-recaptcha-response' => [new ValidCaptcha]],
        );

        $this->assertTrue($validator->passes());
    }
}
