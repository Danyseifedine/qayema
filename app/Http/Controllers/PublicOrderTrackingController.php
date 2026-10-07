<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A guest following an order they placed in the menu. It shows in the
 * menu's own tracking sheet (public/js/menu-order.js), which asks for it
 * here: sent, accepted, on its way or ready, then delivered or picked up, or
 * cancelled. Pusher says when it moves (App\Events\OrderMoved). The address
 * carries the order's tracking token, not its short reference, so nobody
 * finds an order by guessing; opened as a page, it is the menu with the
 * sheet up.
 */
class PublicOrderTrackingController extends Controller
{
    public function show(Request $request, Restaurant $restaurant, string $token): JsonResponse|RedirectResponse
    {
        $order = $this->order($restaurant, $token);
        $locale = in_array($request->query('lang'), $restaurant->menuLanguages(), true)
            ? (string) $request->query('lang')
            : MenuLanguages::default($restaurant);

        if (! $request->wantsJson()) {
            return redirect()->route('public.menu', array_filter([
                'restaurant' => $restaurant->slug,
                'lang' => $locale === MenuLanguages::default($restaurant) ? null : $locale,
                'track' => $token,
            ]));
        }

        app()->setLocale($locale);
        $order->load('items');

        return response()->json(['data' => [
            'reference' => $order->reference,
            'status' => $order->status->value,
            'fulfilment' => $order->fulfilment?->value,
            'table' => $order->table_name,
            'closed' => $order->status->isClosed(),
            // The menu's bar back to the order lets go of a cancelled one an
            // hour after this, and of a delivered one at once.
            'closed_at' => $order->closed_at?->toIso8601String(),
            'html' => view('menu.partials.order-tracking', [
                'order' => $order,
                'restaurant' => $restaurant,
                'locale' => $locale,
            ])->render(),
        ]]);
    }

    /**
     * The order as the menu's cart holds one, for the guest to change it:
     * its lines with their choices, and what they typed. `editable` is false
     * once the restaurant has accepted it.
     */
    public function cart(Restaurant $restaurant, string $token): JsonResponse
    {
        $order = $this->order($restaurant, $token)->load('items');
        $phone = $order->guest_phone !== null ? PhoneNumber::split($order->guest_phone) : ['country' => null, 'national' => ''];

        return response()->json(['data' => [
            'editable' => $order->status->isOpenToGuest(),
            'reference' => $order->reference,
            // Sent back with the change (OrderPlacer::change()).
            'version' => $order->guest_updates,
            'update_url' => route('public.order.update', [$restaurant->slug, $token]),
            'lines' => $order->items
                // A dish deleted since has nothing to go back into the cart as.
                ->filter(fn (OrderItem $item): bool => $item->dish_id !== null)
                ->map(fn (OrderItem $item): array => [
                    'dish' => (string) $item->dish_id,
                    'options' => array_values(array_filter(array_column((array) ($item->options['variants'] ?? []), 'option_id'))),
                    'addons' => array_values(array_filter(array_column((array) ($item->options['addons'] ?? []), 'addon_id'))),
                    'qty' => $item->quantity,
                ])->values()->all(),
            'details' => [
                'fulfilment' => $order->fulfilment?->value,
                // Kept on a change (OrderPlacer::change()); shown, never sent back.
                'table' => $order->table_name,
                'name' => (string) $order->guest_name,
                'country' => $phone['country'],
                'phone' => $phone['national'],
                'address' => (string) $order->address,
                'note' => (string) $order->note,
            ],
        ]]);
    }

    private function order(Restaurant $restaurant, string $token): Order
    {
        abort_unless($restaurant->is_active, 404);

        return $restaurant->orders()->where('tracking_token', $token)->firstOrFail();
    }
}
