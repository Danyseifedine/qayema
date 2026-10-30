<?php

namespace App\Http\Resources;

use App\Support\Price;
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
            // The rate its lines in the second currency were counted at, when it has some.
            'exchange_rate' => $this->exchange_rate === null ? null : Price::rate((string) $this->exchange_rate),
            'note' => $this->note,
            // How the guest gets it and how to reach them; null on a
            // WhatsApp order, which carries none of this.
            'fulfilment' => $this->fulfilment?->value,
            // The table it goes to, as it was named when ordered.
            'table' => $this->table_name,
            'name' => $this->guest_name,
            'phone' => $this->guest_phone,
            'address' => $this->address,
            'map_url' => $this->mapUrl(),
            'placed_at' => $this->placed_at?->toIso8601String(),
            'accepted_at' => $this->accepted_at?->toIso8601String(),
            // The guest changed it after placing it, and when last.
            'guest_updated_at' => $this->guest_updated_at?->toIso8601String(),
            'guest_updates' => (int) $this->guest_updates,
            // When the restaurant last changed what it holds.
            'owner_updated_at' => $this->owner_updated_at?->toIso8601String(),
            // Sent to the restaurant's POS (Connect to POS): sending, sent
            // (the POS handles it, with its number) or failed (why); null
            // for an order that was never sent.
            'pos' => $this->pos_status === null ? null : [
                'status' => $this->pos_status,
                'number' => $this->pos_number,
                'error' => $this->pos_error,
            ],
            // Why the POS called it off, when it said.
            'cancel_reason' => $this->cancel_reason,
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item): array => [
                'id' => $item->id,
                'name' => $item->name,
                // The guest's choices as they were: {variants, addons}, or null.
                'options' => $item->options,
                'unit_price' => (string) $item->unit_price,
                'quantity' => $item->quantity,
                'line_total' => (string) $item->line_total,
                // The line's own currency: the order's, or the second one it was priced in.
                'currency' => $item->currency ?? $this->currency,
                // Bought on an offer: its title then, and the unit price
                // before the discount; both null otherwise.
                'offer' => $item->offer_title === null ? null : [
                    'title' => $item->offer_title,
                    'full_price' => (string) $item->full_price,
                ],
            ])->all()),
        ];
    }
}
