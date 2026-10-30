<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\Orders\OrderNews;
use App\Services\Pos\PosClient;
use App\Services\Pos\PosException;
use App\Services\Pos\PosOrders;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Sends an order placed in the menu to the restaurant's POS, tried again
 * for about forty minutes while the POS does not answer. Taken: the order
 * keeps the POS's number and the POS handles it from here. Refused, or
 * never answered: it stays on the Orders page marked "Not sent to your POS"
 * with the reason, and the owner may try again. The same order sent twice is
 * one order in the POS.
 */
class SendOrderToPos implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Seconds before each try after the first. */
    public const WAITS = [10, 30, 120, 600, 1800];

    public int $tries = 6;

    public int $uniqueFor = 3600;

    public function __construct(public readonly int $orderId) {}

    public function uniqueId(): string
    {
        return (string) $this->orderId;
    }

    public function handle(PosOrders $orders): void
    {
        $order = Order::query()->with(['restaurant.posConnection', 'items'])->find($this->orderId);
        $connection = $order?->restaurant?->posConnection;

        if ($order === null || $order->pos_status === Order::POS_SENT) {
            return;
        }

        if ($connection === null || ! $order->restaurant->isConnectedToPos()) {
            $this->notSent($order, __('Your menu is not connected to a POS any more.'));

            return;
        }

        try {
            $answer = PosClient::for($connection)->placeOrder($orders->payload($order));
        } catch (PosException $exception) {
            if ($exception->retry && $this->attempts() < $this->tries) {
                $order->forceFill(['pos_error' => Str::limit($exception->getMessage(), 490)])->save();
                $this->release(self::WAITS[$this->attempts() - 1] ?? 1800);

                return;
            }

            $this->notSent($order, $exception->getMessage());

            return;
        }

        $order->forceFill([
            'pos_status' => Order::POS_SENT,
            'pos_number' => is_string($answer['pos_number'] ?? null) ? mb_substr($answer['pos_number'], 0, 30) : null,
            'pos_error' => null,
        ])->save();
        OrderNews::fromGuest($order);
    }

    /**
     * Kept on the Orders page with the reason, for the owner to see to.
     */
    private function notSent(Order $order, string $reason): void
    {
        $order->forceFill(['pos_status' => Order::POS_FAILED, 'pos_error' => Str::limit($reason, 490)])->save();
        OrderNews::fromGuest($order);
    }
}
