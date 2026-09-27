<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\IndexOrdersRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The owner's own orders. Read and a status change — an order's contents are
 * written once, by the guest who placed it, and never edited afterwards.
 */
class OrderController extends Controller
{
    use ResolvesRestaurant;

    private const PER_PAGE = 30;

    public function index(IndexOrdersRequest $request): AnonymousResourceCollection
    {
        $restaurant = $this->restaurant($request);

        $orders = $restaurant->orders()
            ->with('items')
            ->when($request->validated('status'), fn ($query, $status) => $query->where('status', $status))
            ->paginate(self::PER_PAGE);

        return OrderResource::collection($orders)->additional([
            'meta' => [
                // The count the owner actually cares about: what is still
                // waiting, whatever page they are looking at.
                'open' => $restaurant->orders()->where('status', OrderStatus::Placed)->count(),
            ],
        ]);
    }

    public function update(UpdateOrderRequest $request, Order $order): OrderResource
    {
        abort_unless($order->restaurant_id === $this->restaurant($request)->id, 403);

        $order->update(['status' => $request->validated('status')]);

        return new OrderResource($order->fresh()->load('items'));
    }
}
