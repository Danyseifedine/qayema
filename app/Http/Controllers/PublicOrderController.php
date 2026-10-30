<?php

namespace App\Http\Controllers;

use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Http\Requests\PlaceOrderRequest;
use App\Models\Restaurant;
use App\Services\Menu\OpeningHours;
use App\Services\Orders\OrderChanged;
use App\Services\Orders\OrderLocked;
use App\Services\Orders\OrderNews;
use App\Services\Orders\OrderPlacer;
use App\Services\Orders\PricesChanged;
use App\Services\Orders\WhatsAppLink;
use App\Services\Pos\PosOrders;
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
 *
 * An order to the table (dine-in, from a table's QR code) is its own feature
 * with its own way in (Restaurant::dineInChannel()): placed in the menu, it
 * waits on the Table orders page; on WhatsApp, the message names the table.
 */
class PublicOrderController extends Controller
{
    public function store(PlaceOrderRequest $request, Restaurant $restaurant, OrderPlacer $placer, PosOrders $posOrders): JsonResponse
    {
        // Same gate as the menu itself, plus the package flag. A 404 rather
        // than a 403: a restaurant that does not take orders should not even
        // admit the endpoint exists.
        abort_unless($restaurant->menuOnline(), 404);
        // An order to the table goes the table's way, whichever way delivery
        // and pickup come in (if they come in at all). Without ordering at
        // the table, a table's code on a WhatsApp order only labels it.
        $atTable = $restaurant->takesDineIn()
            && ($request->isDineIn() || ($request->mode() === OrderChannel::WhatsApp && $request->table() !== null));
        $channel = $atTable ? $restaurant->dineInChannel() : $restaurant->orderChannel();
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

        try {
            $order = $placer->place($restaurant, $request->validated('items'), $locale, $request->details());
        } catch (PricesChanged $changed) {
            return $this->pricesChanged($changed);
        }

        // Sent on to the restaurant's POS when it takes its orders there.
        if ($posOrders->sends($restaurant, $order)) {
            $posOrders->queue($order);
        }

        OrderNews::fromGuest($order);

        return response()->json([
            'data' => [
                'reference' => $order->reference,
                'total' => (string) $order->total,
                'channel' => $channel->value,
                'whatsapp_url' => $channel === OrderChannel::WhatsApp ? WhatsAppLink::forOrder($restaurant, $order, $locale) : null,
                // The guest's page for following it, in their language, while
                // the package tracks orders.
                'tracking_url' => $restaurant->tracksOrders() ? $order->trackingUrl($locale) : null,
                // What sold out while it sat in the cart, so the guest is told.
                'unavailable' => $order->leftOut,
            ],
        ], 201);
    }

    /**
     * The guest changes their order from the menu, until the restaurant
     * accepts it. The cart is checked and priced exactly as a new order is.
     */
    public function update(PlaceOrderRequest $request, Restaurant $restaurant, string $token, OrderPlacer $placer): JsonResponse
    {
        // Without order tracking, PlaceOrderRequest has already answered 404.
        abort_unless($restaurant->menuOnline(), 404);
        $order = $restaurant->orders()->where('tracking_token', $token)->firstOrFail();

        // Changed where it was placed: in the menu, while it still takes
        // that kind of order.
        $stillTaken = $order->fulfilment === Fulfilment::DineIn
            ? $restaurant->takesDineIn()
            : $restaurant->orderChannel() === OrderChannel::Menu;

        if (! $stillTaken || $request->mode() !== OrderChannel::Menu) {
            return response()->json(['message' => __('This menu was just updated. Please refresh the page to order.')], 409);
        }

        $locale = $request->guestLocale();
        // The restaurant is already in hand; the order need not read it again.
        $order->setRelation('restaurant', $restaurant);

        try {
            $change = $placer->change($order, $request->validated('items'), $locale, $request->details(), $request->version());
            $order = $change->order;
            OrderNews::fromGuest($order);
        } catch (OrderLocked) {
            return response()->json(['message' => __('The restaurant has already accepted your order, so it can no longer be changed. Call them if you need to.')], 409);
        } catch (OrderChanged) {
            return response()->json(['message' => __('Your order was changed from another page meanwhile. Open it to see it as it is now, then try again.')], 409);
        } catch (PricesChanged $changed) {
            return $this->pricesChanged($changed);
        }

        return response()->json([
            'data' => [
                'reference' => $order->reference,
                'total' => (string) $order->total,
                'channel' => OrderChannel::Menu->value,
                'whatsapp_url' => null,
                'tracking_url' => $order->trackingUrl($locale),
                // What the change went without, so the guest is told.
                'unavailable' => $change->unavailable,
            ],
        ]);
    }

    /**
     * The cart costs more than the guest saw: nothing is placed, and the
     * page is given each dish's price now, and the deal it has, to show
     * before they send again.
     */
    private function pricesChanged(PricesChanged $changed): JsonResponse
    {
        return response()->json([
            'message' => __('Some prices changed since you opened the menu (an offer may have ended). Check your cart, then send it again.'),
            'prices' => (object) $changed->prices,
            'deals' => (object) $changed->deals,
        ], 409);
    }

    /** No hours written down means no hours to keep. */
    private function isOpen(Restaurant $restaurant): bool
    {
        $hours = OpeningHours::for($restaurant);

        return $hours->isEmpty() || $hours->isOpenNow();
    }
}
