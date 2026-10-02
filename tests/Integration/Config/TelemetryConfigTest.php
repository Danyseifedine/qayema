<?php

namespace Tests\Integration\Config;

use Tests\TestCase;

/**
 * Traces and logs go to Grafana Cloud only when GRAFANA_OTLP_ENDPOINT is set
 * (production, or a developer who wants to look), never from the tests.
 */
class TelemetryConfigTest extends TestCase
{
    /** @var array<string, string|null> */
    private array $saved = [];

    protected function tearDown(): void
    {
        foreach ($this->saved as $key => $value) {
            $this->setEnv($key, $value);
        }

        parent::tearDown();
    }

    public function test_nothing_is_sent_without_a_grafana_endpoint(): void
    {
        $this->assertTrue(config('opentelemetry.disabled'));
        $this->assertSame('null', config('opentelemetry.metrics.exporter'));
        $this->assertNotContains('otlp', config('logging.channels.stack.channels'));
    }

    public function test_with_grafana_set_traces_and_logs_go_there_with_its_header_as_written(): void
    {
        $this->setEnv('GRAFANA_OTLP_ENDPOINT', 'https://otlp-gateway.example/otlp');
        $this->setEnv('GRAFANA_AUTH_HEADER', 'Basic MTIzOmFiYw==');
        $this->setEnv('OTEL_SDK_DISABLED', 'false');

        $telemetry = require config_path('opentelemetry.php');
        $logging = require config_path('logging.php');

        $this->assertFalse($telemetry['disabled']);
        $this->assertSame('qayema', $telemetry['service_name']);
        $this->assertSame('https://otlp-gateway.example/otlp', $telemetry['exporters']['otlp']['endpoint']);
        // A map, not "Authorization=Basic%20...": the SDK would send the
        // encoded form as written and Grafana answers Unauthorized.
        $this->assertSame(['Authorization' => 'Basic MTIzOmFiYw=='], $telemetry['exporters']['otlp']['traces_headers']);
        $this->assertSame(['Authorization' => 'Basic MTIzOmFiYw=='], $telemetry['exporters']['otlp']['logs_headers']);
        $this->assertSame('null', $telemetry['metrics']['exporter']);
        $this->assertSame(config('app.env'), $telemetry['resource_attributes']['deployment.environment.name']);

        $this->assertSame(['single', 'otlp'], $logging['channels']['stack']['channels']);
        $this->assertSame('info', $logging['channels']['otlp']['level']);
    }

    public function test_the_tests_themselves_never_send(): void
    {
        $phpunit = file_get_contents(base_path('phpunit.xml'));

        $this->assertStringContainsString('<env name="OTEL_SDK_DISABLED" value="true"/>', $phpunit);
        $this->assertStringContainsString('OTEL_SDK_DISABLED=true', file_get_contents(base_path('tests/E2e/.env.e2e')));
    }

    private function setEnv(string $key, ?string $value): void
    {
        if (! array_key_exists($key, $this->saved)) {
            $this->saved[$key] = $_ENV[$key] ?? null;
        }

        if ($value === null) {
            unset($_ENV[$key], $_SERVER[$key]);
            putenv($key);

            return;
        }

        $_ENV[$key] = $_SERVER[$key] = $value;
        putenv("{$key}={$value}");
    }
}
