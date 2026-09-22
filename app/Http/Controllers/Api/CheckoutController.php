<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CheckoutRequest;
use App\Models\CoinPack;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    /**
     * Prepare a Paddle overlay checkout for a coin pack. We resolve the pack's
     * price server-side, make sure the buyer is a Paddle customer (so the
     * webhook can map the payment back to them), and hand the SPA what
     * `Paddle.Checkout.open` needs. The coins themselves are credited later,
     * server-side, when `transaction.completed` arrives.
     */
    public function store(CheckoutRequest $request): JsonResponse
    {
        $user = $request->user();

        $pack = CoinPack::query()
            ->sellable()
            ->findOrFail($request->validated('pack_id'));

        $customer = $user->createAsCustomer();

        return response()->json([
            'customer_id' => $customer->paddle_id,
            'items' => [[
                'priceId' => $pack->paddlePriceId(),
                'quantity' => (int) $request->validated('quantity', 1),
            ]],
            'coins' => $pack->coins * (int) $request->validated('quantity', 1),
        ]);
    }
}
