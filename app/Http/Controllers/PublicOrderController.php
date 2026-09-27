<?php

namespace App\Http\Controllers;

use App\Http\Requests\PlaceOrderRequest;
use App\Models\Restaurant;
use App\Services\Global\MenuLanguages;
use App\Services\Global\OrderPlacer;
use App\Services\Global\WhatsAppLink;
use Illuminate\Http\JsonResponse;

/**
 * A guest placing an order from the public menu.
 *
 * The order is stored so the owner has a record, and the response carries a
 * WhatsApp link the page sends the guest to — that hand-off is what actually
 * reaches the owner, since nothing here is realtime.
 */
class PublicOrderController extends Controller
{
    public function store(PlaceOrderRequest $request, Restaurant $restaurant, OrderPlacer $placer): JsonResponse
    {
        // Same gate as the menu itself, plus the package flag. A 404 rather
        // than a 403: a restaurant that does not take orders should not even
        // admit the endpoint exists.
        abort_unless($restaurant->is_active, 404);
        abort_unless($restaurant->takesOrders(), 404);

        // The guest's menu language: the WhatsApp text and any error come
        // back in the language they were reading.
        $locale = in_array($request->validated('locale'), $restaurant->menuLanguages(), true)
            ? (string) $request->validated('locale')
            : MenuLanguages::default($restaurant);
        app()->setLocale($locale);

        $order = $placer->place(
            $restaurant,
            $request->validated('items'),
            $request->validated('note'),
            $locale,
        );

        return response()->json([
            'data' => [
                'reference' => $order->reference,
                'total' => (string) $order->total,
                'whatsapp_url' => WhatsAppLink::forOrder($restaurant, $order),
            ],
        ], 201);
    }
}
