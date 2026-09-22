<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Paddle\Transaction;

class PurchaseController extends Controller
{
    /**
     * A short-lived Paddle-hosted invoice PDF URL for one of the owner's own
     * transactions. 404 for foreign transactions so their existence never leaks.
     */
    public function invoice(Request $request, Transaction $transaction): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $transaction->billable_type === $user->getMorphClass()
                && (int) $transaction->billable_id === (int) $user->getKey(),
            404,
        );

        $url = $transaction->invoice_number ? $transaction->invoicePdf() : null;

        abort_if($url === null, 404, __('No invoice is available for this purchase.'));

        return response()->json(['url' => $url]);
    }
}
