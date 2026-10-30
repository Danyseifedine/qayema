<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\SyncPosCatalog;
use App\Models\PosConnection;
use App\Services\Pos\PosLink;
use App\Services\Pos\PosOrders;
use App\Services\Pos\PosSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * What a POS tells Qayema, signed with the link's secret (PosSignature):
 * the catalog changed (the menu pulls it), the link was revoked there, or
 * an order sent to it moved on (PosOrders::reported()). Public, outside CSRF, rate
 * limited; an unsigned, stale or repeated call is refused.
 */
class PosWebhookController extends Controller
{
    public function __invoke(Request $request, PosLink $link, PosOrders $orders): JsonResponse
    {
        $uuid = (string) $request->header('X-Pos-Connection', '');
        $connection = $uuid === '' ? null : PosConnection::query()->with('restaurant')->where('pos_uuid', $uuid)->first();
        $body = $request->getContent();
        $signature = $request->header('X-Pos-Signature');

        if ($connection === null || ! PosSignature::verify($connection->signing_secret, $request->header('X-Pos-Timestamp'), $signature, $body)) {
            return response()->json(['message' => 'Invalid signature.'], 401);
        }

        // The same signed call twice (a copy sent again within its five
        // minutes) is answered but not acted on.
        if (! Cache::add('pos-webhook:'.hash('sha256', (string) $signature), true, PosSignature::MAX_AGE_SECONDS * 2)) {
            return response()->json(['message' => 'Already received.']);
        }

        $restaurant = $connection->restaurant;

        match ($request->json('event')) {
            'catalog.changed' => $restaurant->isConnectedToPos() ? SyncPosCatalog::dispatch($restaurant->id) : null,
            'connection.revoked' => $link->unlink($restaurant, tellPos: false),
            'order.status' => $orders->reported($restaurant, $request->json()->all()),
            default => null,
        };

        return response()->json(['message' => 'Received.'], 202);
    }
}
