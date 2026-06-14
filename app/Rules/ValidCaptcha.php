<?php

namespace App\Rules;

use App\Services\ThirdParty\Captcha;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Request;

class ValidCaptcha implements ValidationRule
{
    /**
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! app(Captcha::class)->verify(is_string($value) ? $value : null, Request::ip())) {
            $fail(__('auth.captcha'));
        }
    }
}
