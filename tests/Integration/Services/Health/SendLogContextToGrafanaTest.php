<?php

namespace Tests\Integration\Services\Health;

use App\Services\Health\SendLogContextToGrafana;
use Illuminate\Log\Logger;
use Monolog\Handler\TestHandler;
use Monolog\Logger as Monolog;
use Monolog\LogRecord;
use Psr\Log\NullLogger;
use Tests\TestCase;

/**
 * App\Services\Health\SendLogContextToGrafana: the request's Context (in a
 * log record's `extra`) reaches the Grafana handler as `context`, which is
 * all that handler sends.
 */
class SendLogContextToGrafanaTest extends TestCase
{
    public function test_the_otlp_channel_is_tapped(): void
    {
        $this->assertSame([SendLogContextToGrafana::class], config('logging.channels.otlp.tap'));
    }

    public function test_the_handler_gets_the_requests_context_with_the_lines_own_winning(): void
    {
        $handler = new TestHandler;
        $monolog = new Monolog('test', [$handler]);
        // What Laravel's ContextLogProcessor does with Context::add().
        $monolog->pushProcessor(fn (LogRecord $record): LogRecord => $record->with(extra: ['route' => 'public.menu', 'restaurant' => 'olive', 'page' => 'from the request']));

        (new SendLogContextToGrafana)(new Logger($monolog));
        $monolog->warning('Something happened', ['page' => 'from the line']);

        $this->assertSame(
            ['route' => 'public.menu', 'restaurant' => 'olive', 'page' => 'from the line'],
            $handler->getRecords()[0]->context,
        );
    }

    public function test_a_logger_that_is_not_monolog_is_left_alone(): void
    {
        $logger = new Logger(new NullLogger);

        (new SendLogContextToGrafana)($logger);

        $this->assertInstanceOf(NullLogger::class, $logger->getLogger());
    }
}
