<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ClientErrorRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * What went wrong in an owner's dashboard, logged like a server error so it
 * reaches Grafana (docs/grafana-alerts.md): a page that broke, a script
 * error, a promise nobody caught, and what its Content-Security-Policy
 * blocked or would block. Our own backend only; no third-party telemetry.
 */
class ClientErrorController extends Controller
{
    /** A browser's CSP report is a few hundred bytes; anything bigger is not one. */
    private const CSP_REPORT_BYTES = 8192;

    public function store(ClientErrorRequest $request): Response
    {
        $error = $request->validated();

        Log::warning('Dashboard error: '.Str::limit($error['message'], 200), [
            'source' => $error['source'],
            'page' => $error['page'] ?? null,
            'release' => $error['release'] ?? null,
            'stack' => $error['stack'] ?? null,
            'user_agent' => Str::limit((string) $request->userAgent(), 300),
        ]);

        return response()->noContent();
    }

    /**
     * A browser's `report-uri` report: `{"csp-report": {...}}`, sent as
     * `application/csp-report`, which Laravel does not read as JSON.
     */
    public function csp(Request $request): Response
    {
        $body = $request->getContent();
        $report = strlen($body) <= self::CSP_REPORT_BYTES ? json_decode($body, true)['csp-report'] ?? null : null;

        if (is_array($report)) {
            $text = fn (string $key): ?string => isset($report[$key]) && is_scalar($report[$key])
                ? Str::limit((string) $report[$key], 300)
                : null;

            Log::warning('Dashboard CSP blocked: '.($text('effective-directive') ?? $text('violated-directive') ?? 'unknown'), [
                'blocked' => $text('blocked-uri'),
                'page' => $text('document-uri'),
                'source' => $text('source-file'),
                'line' => $text('line-number'),
                'disposition' => $text('disposition'),
            ]);
        }

        return response()->noContent();
    }
}
