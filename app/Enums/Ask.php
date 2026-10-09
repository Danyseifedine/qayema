<?php

namespace App\Enums;

/**
 * Whether a WhatsApp order asks the guest for one detail (their name, their
 * phone, a delivery address): not at all, if they like, or before it goes.
 * The owner sets each on the Features page (Restaurant::whatsappAsks()).
 */
enum Ask: string
{
    case Off = 'off';
    case Optional = 'optional';
    case Required = 'required';
}
