<?php

namespace App\Services\Orders;

use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Models\DiningTable;

/**
 * Everything about an order besides its lines: how it came in, and for one
 * placed in the menu, how to reach the guest and where the food goes: an
 * address, the counter, or the table it was ordered from.
 */
final readonly class OrderDetails
{
    public function __construct(
        public OrderChannel $channel = OrderChannel::WhatsApp,
        public ?string $note = null,
        public ?Fulfilment $fulfilment = null,
        public ?string $name = null,
        public ?string $phone = null,
        public ?string $address = null,
        public ?string $latitude = null,
        public ?string $longitude = null,
        public ?string $clientToken = null,
        public ?DiningTable $table = null,
    ) {}

    /** @return array<string, mixed> the order columns these fill */
    public function attributes(): array
    {
        return [
            'channel' => $this->channel,
            'note' => self::clean($this->note),
            'fulfilment' => $this->fulfilment,
            'guest_name' => self::clean($this->name),
            'guest_phone' => $this->phone,
            // Pickup needs no address, so one sent anyway is not kept.
            'address' => $this->fulfilment === Fulfilment::Delivery ? self::clean($this->address) : null,
            'latitude' => $this->fulfilment === Fulfilment::Delivery ? $this->latitude : null,
            'longitude' => $this->fulfilment === Fulfilment::Delivery ? $this->longitude : null,
            'client_token' => $this->clientToken,
            // Its name as it is now, so renaming the table later leaves the
            // order saying where it went.
            'table_id' => $this->table?->id,
            'table_name' => $this->table?->name,
        ];
    }

    private static function clean(?string $text): ?string
    {
        return $text !== null && trim($text) !== '' ? trim($text) : null;
    }
}
