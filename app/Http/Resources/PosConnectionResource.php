<?php

namespace App\Http\Resources;

use App\Enums\OrderChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A restaurant's link to its POS branch, as the Connect to POS card shows
 * it. The key and the signing secret never leave the server.
 *
 * @mixin \App\Models\PosConnection
 */
class PosConnectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'address' => $this->address,
            'company' => $this->company,
            'branch' => $this->branch,
            'currency' => $this->currency,
            'locales' => $this->locales ?? [],
            'linked_at' => $this->created_at?->toIso8601String(),
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
            'last_attempt_at' => $this->last_attempt_at?->toIso8601String(),
            'last_error' => $this->last_error,
            // Over an hour without an answer: since when (null while it answers).
            'unreachable_since' => $this->unreachableSince()?->toIso8601String(),
            // Orders placed in the menu go to the POS too.
            'sends_orders' => (bool) $this->sends_orders,
            // Whether guests place orders in the menu at all (for delivery,
            // pickup or at the table); WhatsApp orders cannot be sent.
            'orders_in_menu' => $this->restaurant->orderChannel() === OrderChannel::Menu || $this->restaurant->dineInChannel() === OrderChannel::Menu,
            'problems' => array_values(array_map(fn (array $problem): array => ['type' => (string) ($problem['type'] ?? ''), 'message' => (string) ($problem['message'] ?? '')], $this->problems ?? [])),
        ];
    }
}
