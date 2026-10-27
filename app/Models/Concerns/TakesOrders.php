<?php

namespace App\Models\Concerns;

use App\Enums\Ask;
use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Services\Orders\WhatsAppLink;

/**
 * Whether and how guests order: the channel of delivery and pickup, orders
 * at the table, and what a WhatsApp order asks the guest for. The full rules
 * are docs/architecture/ordering.md.
 */
trait TakesOrders
{
    /**
     * Guests can order for delivery or pickup: the package has a way for it
     * (WhatsApp, the menu, or both) and the owner has not switched it off.
     */
    public function takesOrders(): bool
    {
        return $this->orderWays() !== [] && ! $this->isSwitchedOff('orders');
    }

    /**
     * How guests' orders for delivery or pickup reach this restaurant, or
     * null when it takes none. Each way is a package switch of its own
     * (`ordering` for WhatsApp, `menu_ordering` for the menu): with both the
     * owner picks (`order_mode`), with one that is the way, so a package
     * change never leaves guests with a cart that goes nowhere.
     */
    public function orderChannel(): ?OrderChannel
    {
        return $this->takesOrders() ? $this->orderMode() : null;
    }

    /**
     * The way delivery and pickup go on this package, whether or not the
     * owner switched orders off (what the dashboard shows as chosen).
     */
    public function orderMode(): OrderChannel
    {
        $ways = $this->orderWays();

        if (count($ways) === 1) {
            return $ways[0];
        }

        // Both: the owner's pick. Neither: WhatsApp, the menu being the
        // way a package has to give.
        return $ways !== [] && $this->order_mode === OrderChannel::Menu->value ? OrderChannel::Menu : OrderChannel::WhatsApp;
    }

    /**
     * The ways the package lets delivery and pickup orders come in.
     *
     * @return list<OrderChannel>
     */
    public function orderWays(): array
    {
        $entitlements = $this->entitlements();

        return array_values(array_filter([
            $entitlements->can(Feature::Ordering) ? OrderChannel::WhatsApp : null,
            $entitlements->can(Feature::MenuOrdering) ? OrderChannel::Menu : null,
        ]));
    }

    /** A guest follows an order placed in the menu and may change it until it is accepted (`order_tracking`). */
    public function tracksOrders(): bool
    {
        return $this->entitlements()->can(Feature::OrderTracking);
    }

    /**
     * The kinds of order that leave the restaurant (delivery, pickup) it
     * accepts in the menu, in a fixed order. Nothing chosen yet means both.
     * Ordering at the table is not one of them: it is takesDineIn().
     *
     * @return array<int, string>
     */
    public function orderTypes(): array
    {
        $types = array_values(array_intersect(Fulfilment::away(), (array) $this->order_types));

        return $types === [] ? Fulfilment::away() : $types;
    }

    /**
     * Every kind of order this restaurant takes: the delivery and pickup it
     * chose, and dine-in while that is on. Whether delivery and pickup are
     * taken in the menu right now is the channel's question, answered by the
     * order controller (a page built for the other way is told to refresh).
     * Dine-in still needs a table's code to order to.
     *
     * @return array<int, string>
     */
    public function menuFulfilments(): array
    {
        return [
            ...$this->orderTypes(),
            ...$this->takesDineIn() ? [Fulfilment::DineIn->value] : [],
        ];
    }

    /**
     * Guests at a table can order to it: the package has a way for it, the
     * owner has not switched it off, and that way can be used now
     * (dineInChannel()). A feature of its own, apart from ordering: it works
     * whichever way delivery and pickup come in, or with them off.
     */
    public function takesDineIn(): bool
    {
        return $this->dineInChannel() !== null;
    }

    /**
     * How orders at the table reach this restaurant, or null when it takes
     * none. Each way is a package switch of its own (`dine_in` for the menu,
     * the Table orders page; `dine_in_whatsapp`): with both the owner picks
     * (`dine_in_mode`), with one that is the way. WhatsApp needs a number
     * to send to: without one, they come in the menu when the package has
     * it, and not at all when WhatsApp is its only way.
     */
    public function dineInChannel(): ?OrderChannel
    {
        if ($this->isSwitchedOff('dine_in')) {
            return null;
        }

        $ways = $this->dineInWays();
        $whatsapp = in_array(OrderChannel::WhatsApp, $ways, true) && WhatsAppLink::internationalNumber($this) !== null;
        $menu = in_array(OrderChannel::Menu, $ways, true);

        return match (true) {
            $whatsapp && $menu => $this->dine_in_mode === OrderChannel::WhatsApp->value ? OrderChannel::WhatsApp : OrderChannel::Menu,
            $whatsapp => OrderChannel::WhatsApp,
            $menu => OrderChannel::Menu,
            default => null,
        };
    }

    /**
     * The way orders at the table go on this package as the owner sees it
     * chosen: the only way when there is one, the owner's choice when both
     * (WhatsApp then still needs a number, dineInChannel()).
     */
    public function dineInMode(): OrderChannel
    {
        $ways = $this->dineInWays();

        if (count($ways) === 1) {
            return $ways[0];
        }

        return $this->dine_in_mode === OrderChannel::WhatsApp->value ? OrderChannel::WhatsApp : OrderChannel::Menu;
    }

    /**
     * The ways the package lets orders at the table come in.
     *
     * @return list<OrderChannel>
     */
    public function dineInWays(): array
    {
        $entitlements = $this->entitlements();

        return array_values(array_filter([
            $entitlements->can(Feature::DineIn) ? OrderChannel::Menu : null,
            $entitlements->can(Feature::DineInWhatsApp) ? OrderChannel::WhatsApp : null,
        ]));
    }

    /**
     * What a WhatsApp order asks the guest for, per way in: delivery and
     * pickup (`away`: name, phone, and an address, which also asks delivery
     * or pickup) and orders at the table (`table`: name, phone). Each
     * Ask::value; anything not set is off.
     *
     * @return array{away: array{name: string, phone: string, address: string}, table: array{name: string, phone: string}}
     */
    public function whatsappAsks(): array
    {
        $stored = (array) $this->whatsapp_fields;
        $asks = [];

        foreach (self::WHATSAPP_ASKS as $group => $fields) {
            foreach ($fields as $field) {
                $asks[$group][$field] = Ask::tryFrom((string) ($stored[$group][$field] ?? ''))->value ?? Ask::Off->value;
            }
        }

        /** @var array{away: array{name: string, phone: string, address: string}, table: array{name: string, phone: string}} $asks */
        return $asks;
    }
}
