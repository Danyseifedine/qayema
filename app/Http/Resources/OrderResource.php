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
            // How the guest gets it and how to reach them; null on a
            // WhatsApp order, which carries none of this.
            'fulfilment' => $this->fulfilment?->value,
            'name' => $this->guest_name,
            'phone' => $this->guest_phone,
            'address' => $this->address,
            'map_url' => $this->mapUrl(),
            'placed_at' => $this->placed_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            // The guest changed it after placing it, and when last.
            'guest_updated_at' => $this->guest_updated_at?->toIso8601String(),
            'guest_updates' => (int) $this->guest_updates,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'name' => $item->name,
                // The guest's choices as they were: {variants, addons}, or null.
                'options' => $item->options,
                'quantity' => $item->quantity,
                'line_total' => (string) $item->line_total,
            ])->all()),
        ];
    }
}
