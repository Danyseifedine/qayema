<?php

namespace App\Enums;

/**
 * Where an offer shows on the guest menu, one or more: an offer that is a
 * pop-up and an ad shows the ad once the pop-up is closed.
 */
enum OfferPlacement: string
{
    /** A card in the carousel under the cover. */
    case Banner = 'banner';

    /** A card over the menu when it opens; the guest closes it. */
    case Popup = 'popup';

    /** A smaller card at the bottom that closes itself after a few seconds. */
    case Ad = 'ad';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /** How many offers in this place may be switched on at once. */
    public function limit(): Feature
    {
        return match ($this) {
            self::Banner => Feature::OfferBannerLimit,
            self::Popup => Feature::OfferPopupLimit,
            self::Ad => Feature::OfferAdLimit,
        };
    }
}
