<?php

namespace App\Enums;

/**
 * How an order reached the restaurant.
 *
 * WhatsApp: the guest was sent to WhatsApp with the order written out. We
 * only know they opened it, not that they sent it or that it was served.
 * Menu: the order was placed in the menu and waits on the Orders page.
 */
enum OrderChannel: string
{
    case WhatsApp = 'whatsapp';
    case Menu = 'menu';
}
