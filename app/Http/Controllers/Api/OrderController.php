<?php

namespace App\Http\Controllers\Api;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

/**
 * The owner's own orders. Read and a status change — an order's contents are
 * written once, by the guest who placed it, and never edited afterwards.
 */
class OrderController extends Controller
{
    private const PER_PAGE = 30;

    public function index(Request $request): AnonymousResourceCollection
    {
        $restaurant = $this->restaurant($request);

        $validated = $request->validate([
            'status' => ['nullable', Rule::enum(OrderStatus::class)],
        ]);

        $orders = $restaurant->orders()
            ->with('items')
            ->when($validated['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->paginate(self::PER_PAGE);

        return OrderResource::collection($orders)->additional([
            'meta' => [
                // The count the owner actually cares about: what is still
                // waiting, whatever page they are looking at.
                'open' => $restaurant->orders()->where('status', OrderStatus::Placed)->count(),
            ],
        ]);
    }

    public function update(Request $request, Order $order): OrderResource
    {
        abort_unless($order->restaurant_id === $this->restaurant($request)->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
        ]);

        $order->update(['status' => $validated['status']]);

        return new OrderResource($order->fresh()->load('items'));
    }

    private function restaurant(Request $request): Restaurant
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403);

        return $restaurant;
    }
}
