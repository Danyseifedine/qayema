<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\EditOrderItemsRequest;
use App\Http\Requests\IndexOrdersRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\Orders\OrderChanged;
use App\Services\Orders\OrderClosed;
use App\Services\Orders\OrderEditor;
use App\Services\Orders\OrderNews;
use App\Services\Orders\OrderPulse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * The owner's own orders: read, moved on, changed (what they hold, by the
 * restaurant: OrderEditor) and deleted. Every line keeps what it was sold
 * as; the owner changes quantities, removes lines and adds dishes.
 */
class OrderController extends Controller
{
    use ResolvesRestaurant;

    private const PER_PAGE = 30;

    public function index(IndexOrdersRequest $request): AnonymousResourceCollection
    {
        $restaurant = $this->restaurant($request);
        $status = $request->validated('status');
        $kind = $request->validated('kind');

        // Only orders placed in the menu: a WhatsApp order is a guest who
        // opened WhatsApp, and whether they sent it is not ours to know.
        // Orders to a table and the rest each have a page of their own.
        $ofKind = fn () => $restaurant->orders()
            ->inMenu()
            ->when($kind === 'table', fn ($query) => $query->atTable())
            ->when($kind === 'away', fn ($query) => $query->away());

        $orders = $ofKind()
            ->with('items')
            ->when($status, fn ($query, $status) => $query->where('status', $status))
            ->paginate(self::PER_PAGE);

        return OrderResource::collection($orders)->additional([
            'meta' => [
                // The count the owner actually cares about: what is still
                // waiting, whatever page they are looking at.
                // Filtered to those already, the page's own total is it.
                'open' => $status === OrderStatus::Placed->value
                    ? $orders->total()
                    : $ofKind()->where('status', OrderStatus::Placed)->count(),
            ],
        ]);
    }

    /**
     * Where the orders stand (OrderPulse), for a dashboard that cannot hear
     * Pusher right now and asks once a minute instead.
     */
    public function pulse(Request $request): JsonResponse
    {
        return response()->json(['data' => OrderPulse::for($this->restaurant($request))]);
    }

    /**
     * Moves an order on, on the locked row, so it cannot cross a guest's
     * change (OrderPlacer::change()) or another tab's tap. 409 when the
     * order is no longer where the owner's screen showed it.
     */
    public function update(UpdateOrderRequest $request, Order $order): JsonResponse|OrderResource
    {
        abort_unless($order->restaurant_id === $this->restaurant($request)->id, 403);

        $next = OrderStatus::from($request->validated('status'));
        $seen = $request->validated('guest_updates');

        // Null when it moved (or already stood there); why not, otherwise.
        $refusal = DB::transaction(function () use ($order, $next, $seen): ?array {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            // The same tap twice (a double click, a retry): already done.
            if ($locked->status === $next) {
                return null;
            }

            if (! $locked->status->canMoveTo($next)) {
                return ['order_moved_on', __('This order has already moved on. The list now shows where it stands.')];
            }

            if ($seen !== null && $locked->status === OrderStatus::Placed && $next !== OrderStatus::Cancelled && (int) $seen !== $locked->guest_updates) {
                return ['order_changed', __('The guest just changed this order. Look at it again before taking it on.')];
            }

            $locked->moveTo($next);
            OrderNews::fromOwner($locked);

            return null;
        });

        if ($refusal !== null) {
            return response()->json(['message' => $refusal[1], 'code' => $refusal[0]], 409);
        }

        return new OrderResource($order->refresh()->load('items'));
    }

    /**
     * The restaurant changes what the order holds. 409 when it was cancelled,
     * or when the guest changed it after the owner's screen showed it.
     */
    public function items(EditOrderItemsRequest $request, Order $order, OrderEditor $editor): JsonResponse|OrderResource
    {
        $restaurant = $this->restaurant($request);
        abort_unless($order->restaurant_id === $restaurant->id, 403);
        $order->setRelation('restaurant', $restaurant);

        try {
            $edited = $editor->edit($order, $request->kept(), $request->validated('add'), $request->validated('guest_updates'));
        } catch (OrderClosed) {
            return response()->json(['message' => __('This order was cancelled, so it can no longer be changed.'), 'code' => 'order_closed'], 409);
        } catch (OrderChanged) {
            return response()->json(['message' => __('The guest just changed this order. Look at it again before taking it on.'), 'code' => 'order_changed'], 409);
        }

        // The guest following it sees the new version, other tabs too.
        OrderNews::fromOwner($edited);

        return new OrderResource($edited);
    }

    /**
     * Deletes the order and everything it holds, for good: its lines go with
     * it, and so does it from the analytics. The guest following it is told
     * (their page lets go of an order that is gone).
     */
    public function destroy(Request $request, Order $order): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        abort_unless($order->restaurant_id === $restaurant->id, 403);
        $order->setRelation('restaurant', $restaurant);

        $order->delete();
        OrderNews::fromOwner($order);

        return response()->json(null, 204);
    }
}
