<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\EnforcedCsrf;
use Tests\TestCase;

/**
 * `POST /api/client-errors` and `POST /api/csp-report`: what broke in an
 * owner's dashboard, and what its Content-Security-Policy blocked, reach the
 * log (and Grafana). Public, without a CSRF token, small and throttled.
 */
class ClientErrorsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, mixed> */
    private function report(array $overrides = []): array
    {
        return [
            'message' => 'TypeError: dish.name is undefined',
            'stack' => "TypeError: dish.name is undefined\n    at DishCard",
            'source' => 'render',
            'page' => '/dishes',
            'release' => 'B2-GTCz_',
            ...$overrides,
        ];
    }

    public function test_an_error_from_the_dashboard_is_logged_with_where_it_happened(): void
    {
        Log::spy();

        $this->withHeader('User-Agent', 'Mozilla/5.0 (iPhone)')
            ->postJson(route('api.client-errors'), $this->report())
            ->assertNoContent();

        Log::shouldHaveReceived('warning')->once()->with(
            'Dashboard error: TypeError: dish.name is undefined',
            Mockery::on(fn (array $context): bool => $context['source'] === 'render'
                && $context['page'] === '/dishes'
                && $context['release'] === 'B2-GTCz_'
                && str_contains($context['stack'], 'at DishCard')
                && $context['user_agent'] === 'Mozilla/5.0 (iPhone)'),
        );
    }

    public function test_only_the_message_and_source_are_needed(): void
    {
        Log::spy();

        $this->postJson(route('api.client-errors'), ['message' => 'Script error.', 'source' => 'error'])
            ->assertNoContent();

        Log::shouldHaveReceived('warning')->once()->with(
            'Dashboard error: Script error.',
            Mockery::on(fn (array $context): bool => $context['page'] === null && $context['stack'] === null),
        );
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function badReports(): array
    {
        return [
            'no message' => [['message' => ''], 'message'],
            'a message too long' => [['message' => str_repeat('x', 1001)], 'message'],
            'a stack too long' => [['stack' => str_repeat('x', 4001)], 'stack'],
            'an unknown source' => [['source' => 'server'], 'source'],
            'a page too long' => [['page' => '/'.str_repeat('x', 300)], 'page'],
            'a release too long' => [['release' => str_repeat('x', 41)], 'release'],
        ];
    }

    #[DataProvider('badReports')]
    public function test_a_report_out_of_shape_is_refused_and_not_logged(array $overrides, string $field): void
    {
        Log::spy();

        $this->postJson(route('api.client-errors'), $this->report($overrides))
            ->assertUnprocessable()
            ->assertJsonValidationErrors($field);

        Log::shouldNotHaveReceived('warning');
    }

    public function test_the_dashboard_sends_it_without_a_csrf_token(): void
    {
        config([
            'sanctum.stateful' => ['localhost'],
            'sanctum.middleware.validate_csrf_token' => EnforcedCsrf::class,
        ]);

        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])
            ->postJson(route('api.client-errors'), $this->report())
            ->assertNoContent();

        $this->withHeaders(['Origin' => 'http://localhost', 'Referer' => 'http://localhost/'])
            ->call('POST', route('api.csp-report'), [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], '{}')
            ->assertNoContent();
    }

    public function test_more_than_ten_a_minute_from_one_address_are_dropped(): void
    {
        config(['cache.default' => 'array']);
        Log::spy();

        foreach (range(1, 10) as $ignored) {
            $this->postJson(route('api.client-errors'), $this->report())->assertNoContent();
        }

        $this->postJson(route('api.client-errors'), $this->report())->assertTooManyRequests();
        Log::shouldHaveReceived('warning')->times(10);
    }

    private function sendCspReport(string $body): void
    {
        $this->call('POST', route('api.csp-report'), [], [], [], ['CONTENT_TYPE' => 'application/csp-report'], $body)
            ->assertNoContent();
    }

    public function test_a_csp_report_is_logged_with_what_was_blocked(): void
    {
        Log::spy();

        $this->sendCspReport(json_encode(['csp-report' => [
            'document-uri' => 'https://dashboard.qayema.com/dishes',
            'violated-directive' => 'img-src',
            'effective-directive' => 'img-src',
            'blocked-uri' => 'https://tracker.example/pixel.gif',
            'source-file' => 'https://dashboard.qayema.com/assets/index-B2-GTCz_.js',
            'line-number' => 12,
            'disposition' => 'report',
        ]]));

        Log::shouldHaveReceived('warning')->once()->with(
            'Dashboard CSP blocked: img-src',
            [
                'blocked' => 'https://tracker.example/pixel.gif',
                'page' => 'https://dashboard.qayema.com/dishes',
                'source' => 'https://dashboard.qayema.com/assets/index-B2-GTCz_.js',
                'line' => '12',
                'disposition' => 'report',
            ],
        );
    }

    public function test_an_older_browsers_csp_report_names_the_violated_directive(): void
    {
        Log::spy();

        $this->sendCspReport(json_encode(['csp-report' => ['violated-directive' => 'script-src', 'blocked-uri' => ['not', 'text']]]));
        $this->sendCspReport(json_encode(['csp-report' => ['document-uri' => '/']]));

        Log::shouldHaveReceived('warning')->with('Dashboard CSP blocked: script-src', Mockery::on(fn (array $context): bool => $context['blocked'] === null))->once();
        Log::shouldHaveReceived('warning')->with('Dashboard CSP blocked: unknown', Mockery::type('array'))->once();
    }

    /** @return array<string, array{0: string}> */
    public static function notReports(): array
    {
        return [
            'not JSON' => ['<html>'],
            'JSON without a report' => ['{"something": "else"}'],
            'a report that is not an object' => ['{"csp-report": "text"}'],
            'too big to be one' => [json_encode(['csp-report' => ['blocked-uri' => str_repeat('x', 9000)]])],
        ];
    }

    #[DataProvider('notReports')]
    public function test_a_body_that_is_not_a_csp_report_is_ignored(string $body): void
    {
        Log::spy();

        $this->sendCspReport($body);

        Log::shouldNotHaveReceived('warning');
    }
}
