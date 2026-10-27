<?php

namespace Tests\Integration\Support;

use App\Support\PerRequest;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * A value several parts of one request ask for is worked out once, and
 * again for the next request, so it is never older than the page.
 */
class PerRequestTest extends TestCase
{
    public function test_a_value_is_worked_out_once_a_request(): void
    {
        $runs = 0;
        $work = function () use (&$runs): int {
            return ++$runs;
        };

        $this->assertSame(1, PerRequest::remember('count', $work));
        $this->assertSame(1, PerRequest::remember('count', $work));
        $this->assertSame(1, $runs);

        // Another key is its own value.
        $this->assertSame(2, PerRequest::remember('other', $work));
    }

    public function test_the_next_request_works_it_out_again(): void
    {
        $this->assertSame('first', PerRequest::remember('word', fn (): string => 'first'));

        $this->app->instance('request', Request::create('/next'));

        $this->assertSame('second', PerRequest::remember('word', fn (): string => 'second'));
    }

    public function test_a_null_answer_is_kept_too(): void
    {
        $runs = 0;
        $work = function () use (&$runs): ?string {
            $runs++;

            return null;
        };

        PerRequest::remember('nothing', $work);
        PerRequest::remember('nothing', $work);

        $this->assertSame(1, $runs);
    }
}
