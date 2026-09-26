<?php

namespace App\Enums;

/**
 * Something a guest did on a public menu. The page sends these; the owner's
 * advanced analytics count them.
 */
enum MenuEventType: string
{
    /** A dish went into the cart — once per press of +. Carries the dish. */
    case DishAdd = 'dish_add';

    /** A category tab was picked. Carries the category. */
    case CategoryOpen = 'category_open';

    /** A search that found something. Carries the term. */
    case Search = 'search';

    /** A search that found nothing — what guests wanted and the menu lacks. */
    case SearchMiss = 'search_miss';

    case WhatsApp = 'whatsapp';

    case Map = 'map';

    case Call = 'call';

    /** A social link was followed. Carries the platform. */
    case Social = 'social';

    /** The guest switched language. Carries the language code. */
    case Language = 'language';

    public function carriesDish(): bool
    {
        return $this === self::DishAdd;
    }

    public function carriesCategory(): bool
    {
        return $this === self::CategoryOpen;
    }

    public function carriesValue(): bool
    {
        return in_array($this, [self::Search, self::SearchMiss, self::Social, self::Language], true);
    }
}
