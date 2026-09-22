<?php

namespace Tests\Support;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;

/**
 * Laravel silently skips CSRF verification while running tests. Swapping this
 * in (via `sanctum.middleware.validate_csrf_token`) turns the check back on so
 * a test can prove what a stale token actually gets.
 */
class EnforcedCsrf extends VerifyCsrfToken
{
    protected function runningUnitTests(): bool
    {
        return false;
    }
}
