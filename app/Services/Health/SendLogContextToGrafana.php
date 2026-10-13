<?php

namespace App\Services\Health;

use Illuminate\Log\Logger;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Logger as Monolog;
use Monolog\LogRecord;

/**
 * The `otlp` log channel's tap (config/logging.php). Laravel's Context (the
 * route, the restaurant and the user, AppServiceProvider) lands in a log
 * record's `extra`, and the OpenTelemetry handler sends only its `context`;
 * this hands the handler both, so each line in Grafana says which request
 * it came from. Only that handler: the log file keeps its lines as they are.
 */
class SendLogContextToGrafana
{
    public function __invoke(Logger $logger): void
    {
        $monolog = $logger->getLogger();

        if (! $monolog instanceof Monolog) {
            return;
        }

        foreach ($monolog->getHandlers() as $handler) {
            if ($handler instanceof ProcessableHandlerInterface) {
                $handler->pushProcessor(
                    fn (LogRecord $record): LogRecord => $record->with(context: [...$record->extra, ...$record->context]),
                );
            }
        }
    }
}
