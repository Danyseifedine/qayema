<?php

namespace App\Support;

/**
 * The most characters each piece of menu text may have, in every language:
 * what a menu really prints, with room to spare. The owner API, onboarding,
 * the admin panel and the writing help all read them from here; the
 * dashboard's twin is src/shared/constants/menu-text-limits.ts (change
 * one, change both). The columns are wider, so text saved before a limit
 * was lowered still shows; it is shortened the next time it is edited.
 */
final class MenuTextLimits
{
    public const DISH_NAME = 60;

    public const INGREDIENTS = 300;

    public const CATEGORY_NAME = 40;

    public const CATEGORY_DESCRIPTION = 120;

    public const RESTAURANT_NAME = 60;

    public const RESTAURANT_DESCRIPTION = 200;

    /** A variant, one of its options or an add-on. */
    public const CHOICE_NAME = 40;

    public const OFFER_TITLE = 60;

    public const OFFER_DESCRIPTION = 200;

    /** The longest of them: what the writing help translates at most. */
    public const LONGEST = self::INGREDIENTS;
}
