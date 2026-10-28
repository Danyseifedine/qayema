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
use App\Services\Orders\OrderMovedOn;
use App\Services\Orders\OrderNews;
use App\Services\Orders\OrderPulse;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

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

        // Unfinished orders first, newest first within each, so one still to
        // see to is never behind "Show more" however old it is. The id
        // breaks ties, so pages never repeat or skip an order.
        $unfinished = [OrderStatus::Placed->value, OrderStatus::Accepted->value, OrderStatus::Ready->value];
        // The list's total and what is still waiting, in one pass.
        $counts = $ofKind()->toBase()->selectRaw(
            ($status === null ? 'count(*)' : 'sum(case when status = ? then 1 else 0 end)').' as total, sum(case when status = ? then 1 else 0 end) as open',
            [...($status === null ? [] : [$status]), OrderStatus::Placed->value],
        )->first();
        $total = (int) $counts?->total;
        $page = Paginator::resolveCurrentPage();
        $orders = new LengthAwarePaginator(
            $total === 0 ? new Collection : $ofKind()
                ->reorder()
                ->orderByRaw('case when status in (?, ?, ?) then 0 else 1 end', $unfinished)
                ->latest('placed_at')
                ->latest('id')
                ->with('items')
                ->when($status, fn ($query, $status) => $query->where('status', $status))
                ->forPage($page, self::PER_PAGE)
                ->get(),
            $total,
            self::PER_PAGE,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );

        return OrderResource::collection($orders)->additional([
            'meta' => [
                // The count the owner actually cares about: what is still
                // waiting, whatever page they are looking at.
                'open' => (int) $counts?->open,
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
     * Moves an order on (OrderEditor::move()). 409 when the order is no
     * longer where the owner's screen showed it.
     */
    public function update(UpdateOrderRequest $request, Order $order, OrderEditor $editor): JsonResponse|OrderResource
    {
        // Also the 403 for an owner without a restaurant (OrderPolicy).
        $this->authorize('manage', $order);

        $seen = $request->validated('guest_updates');

        try {
            $moved = $editor->move($order, OrderStatus::from($request->validated('status')), $seen === null ? null : (int) $seen);
        } catch (OrderMovedOn) {
            return $this->refused(__('This order has already moved on. The list now shows where it stands.'), 'order_moved_on');
        } catch (OrderChanged) {
            return $this->refused(__('The guest just changed this order. Look at it again before taking it on.'), 'order_changed');
        }

        $order->refresh()->load('items');

        if ($moved) {
            OrderNews::fromOwner($order);
        }

        return new OrderResource($order);
    }

    /**
     * The restaurant changes what the order holds. 409 when it was cancelled,
     * or when the guest changed it after the owner's screen showed it.
     */
    public function items(EditOrderItemsRequest $request, Order $order, OrderEditor $editor): JsonResponse|OrderResource
    {
        $restaurant = $this->restaurant($request);
        $this->authorize('manage', $order);
        $order->setRelation('restaurant', $restaurant);

        try {
            $edited = $editor->edit($order, $request->kept(), $request->validated('add'), $request->validated('guest_updates'));
        } catch (OrderClosed) {
            return $this->refused(__('This order was cancelled, so it can no longer be changed.'), 'order_closed');
        } catch (OrderChanged) {
            return $this->refused(__('The guest just changed this order. Look at it again before taking it on.'), 'order_changed');
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
        $this->authorize('manage', $order);
        $order->setRelation('restaurant', $restaurant);

        $order->delete();
        OrderNews::fromOwner($order);

        return response()->json(null, 204);
    }

    /** The order is no longer where the owner's screen showed it. */
    private function refused(string $message, string $code): JsonResponse
    {
        return response()->json(['message' => $message, 'code' => $code], 409);
    }
}
