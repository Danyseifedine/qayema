<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Services\Global\FeatureCatalog;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    /**
     * Prepare a Paddle overlay checkout for the authenticated owner's cart. We
     * resolve each cart id to its Paddle price, ensure the owner is a Paddle
     * customer (so the webhook can map the payment back to them), and hand the
     * SPA everything `Paddle.Checkout.open` needs. Fulfillment happens later,
     * server-side, when the `transaction.completed` webhook arrives.
     */
    public function store(CheckoutRequest $request, FeatureCatalog $catalog): JsonResponse
    {
        $user = $request->user();
        $restaurant = $user->restaurant;

        abort_if($restaurant === null, 403, __('Create your restaurant before purchasing add-ons.'));

        $items = [];

        foreach ($request->validated('items') as $line) {
            $entry = $catalog->find($line['id']);

            $items[] = [
                'priceId' => $entry['price_id'],
                // Paddle prices this add-on per unit (e.g. per dish) with its own
                // minimum, so translate the owner's pack count into the unit
                // quantity Paddle expects.
                'quantity' => (int) $line['quantity'] * $entry['step'],
            ];
        }

        $customer = $user->createAsCustomer();

        return response()->json([
            'customer_id' => $customer->paddle_id,
            'items' => $items,
            'custom_data' => ['restaurant_id' => $restaurant->id],
        ]);
    }
}
