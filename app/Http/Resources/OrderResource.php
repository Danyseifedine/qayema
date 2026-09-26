<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Order
 */
class OrderResource extends JsonResource
{
    /**
     * An order as it was placed. The lines carry their own names and prices
     * rather than pointing at dishes, because the menu moves on and the order
     * must not move with it.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'currency' => $this->currency,
            'total' => (string) $this->total,
            'note' => $this->note,
            'placed_at' => $this->placed_at?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'unit_price' => (string) $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => (string) $item->line_total,
            ])->all()),
        ];
    }
}
