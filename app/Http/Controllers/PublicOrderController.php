<?php

namespace App\Http\Controllers;

use App\Enums\OrderChannel;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Restaurant;
use App\Services\Menu\OpeningHours;
use App\Services\Orders\OrderChanged;
use App\Services\Orders\OrderLocked;
use App\Services\Orders\OrderNews;
use App\Services\Orders\OrderPlacer;
use App\Services\Orders\WhatsAppLink;
use Illuminate\Http\JsonResponse;

/**
 * A guest placing an order from the public menu.
 *
 * The restaurant takes orders one way (Restaurant::orderChannel()):
 * - on WhatsApp, the order is stored and the response carries a WhatsApp
 *   link the page sends the guest to; that hand-off is what reaches the
 *   owner, and we never learn whether it was sent;
 * - in the menu, the order is stored with the guest's phone and address and
 *   waits on the dashboard's Orders page.
 */
class PublicOrderController extends Controller
{
    public function store(PlaceOrderRequest $request, Restaurant $restaurant, OrderPlacer $placer): JsonResponse
    {
        // Same gate as the menu itself, plus the package flag. A 404 rather
        // than a 403: a restaurant that does not take orders should not even
        // admit the endpoint exists.
        abort_unless($restaurant->is_active, 404);
        $channel = $restaurant->orderChannel();
        abort_if($channel === null, 404);

        // Already the app's language (PlaceOrderRequest), for the lines too.
        $locale = $request->guestLocale();

        // The owner changed how orders come in while this guest had the menu
        // open: the page asks for the wrong things, so it has to reload.
        if ($request->mode() !== $channel) {
            return response()->json(['message' => __('This menu was just updated. Please refresh the page to order.')], 409);
        }

        // An order placed in the menu waits for someone to read it; outside
        // the hours nobody will.
        if ($channel === OrderChannel::Menu && ! $this->isOpen($restaurant)) {
            return response()->json(['message' => __('We are closed right now, so we cannot take your order.')], 422);
        }

        $order = $placer->place($restaurant, $request->validated('items'), $locale, $request->details());
        OrderNews::fromGuest($order);

        return response()->json([
            'data' => [
                'reference' => $order->reference,
                'total' => (string) $order->total,
                'channel' => $channel->value,
                'whatsapp_url' => $channel === OrderChannel::WhatsApp ? WhatsAppLink::forOrder($restaurant, $order) : null,
                // The guest's page for following it, in their language.
                'tracking_url' => $order->trackingUrl($locale),
            ],
        ], 201);
    }

    /**
     * The guest changes their order from the menu, until the restaurant
     * accepts it. The cart is checked and priced exactly as a new order is.
     */
    public function update(PlaceOrderRequest $request, Restaurant $restaurant, string $token, OrderPlacer $placer): JsonResponse
    {
        abort_unless($restaurant->is_active, 404);
        $order = $restaurant->orders()->where('tracking_token', $token)->firstOrFail();

        if ($restaurant->orderChannel() !== OrderChannel::Menu || $request->mode() !== OrderChannel::Menu) {
            return response()->json(['message' => __('This menu was just updated. Please refresh the page to order.')], 409);
        }

        $locale = $request->guestLocale();
        $lines = $request->validated('items');
        // Asked before the change: what it leaves out is said, not hidden.
        $unavailable = $placer->unavailable($restaurant, $lines, $locale);

        try {
            $order = $placer->change($order, $lines, $locale, $request->details(), $request->version());
            OrderNews::fromGuest($order);
        } catch (OrderLocked) {
            return response()->json(['message' => __('The restaurant has already accepted your order, so it can no longer be changed. Call them if you need to.')], 409);
        } catch (OrderChanged) {
            return response()->json(['message' => __('Your order was changed from another page meanwhile. Open it to see it as it is now, then try again.')], 409);
        }

        return response()->json([
            'data' => [
                'reference' => $order->reference,
                'total' => (string) $order->total,
                'channel' => OrderChannel::Menu->value,
                'whatsapp_url' => null,
                'tracking_url' => $order->trackingUrl($locale),
                'unavailable' => $unavailable,
            ],
        ]);
    }

    /** No hours written down means no hours to keep. */
    private function isOpen(Restaurant $restaurant): bool
    {
        $hours = OpeningHours::for($restaurant);

        return $hours->isEmpty() || $hours->isOpenNow();
    }
}
