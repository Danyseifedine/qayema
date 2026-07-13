<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Paddle\Transaction;

class PurchaseController extends Controller
{
    /**
     * The owner's purchase history: every Paddle transaction with the feature
     * grants it delivered (matched via restaurant_features.reference, which
     * fulfillment stamps with the Paddle transaction id).
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $grants = $user->restaurant
            ? $user->restaurant->featureGrants()
                ->where('source', 'purchase')
                ->whereNotNull('reference')
                ->with('feature')
                ->get()
                ->groupBy('reference')
            : collect();

        $data = $user->transactions
            ->map(function (Transaction $transaction) use ($grants): array {
                return [
                    'id' => $transaction->id,
                    'invoice_number' => $transaction->invoice_number,
                    'status' => $transaction->status,
                    'total' => (int) $transaction->total,
                    'tax' => (int) $transaction->tax,
                    'currency' => $transaction->currency,
                    'billed_at' => $transaction->billed_at?->toIso8601String(),
                    'items' => $grants->get($transaction->paddle_id, collect())
                        ->filter(fn ($grant) => $grant->feature !== null)
                        ->map(fn ($grant): array => [
                            'slug' => $grant->feature->slug,
                            'name' => [
                                'en' => $grant->feature->getTranslation('name', 'en', false) ?: null,
                                'ar' => $grant->feature->getTranslation('name', 'ar', false) ?: null,
                            ],
                            'kind' => $grant->feature->kind,
                            'value' => (int) $grant->value,
                        ])
                        ->values(),
                ];
            })
            ->values();

        return response()->json(['data' => $data]);
    }

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
