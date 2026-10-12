<?php

namespace App\Models\Concerns;

use App\Services\Menu\MenuCache;

/**
 * A row the public menu shows: saving or deleting it clears its
 * restaurant's menu from Cloudflare (MenuCache), so guests see the change.
 * The row carries `restaurant_id`. A dish's variants, options and add-ons
 * are only written with their dish, whose save covers them.
 */
trait ClearsMenuCache
{
    protected static function bootClearsMenuCache(): void
    {
        $forget = function (self $row): void {
            if ($row->restaurant_id !== null) {
                app(MenuCache::class)->forget((int) $row->restaurant_id);
            }
        };

        static::saved($forget);
        static::deleted($forget);
    }
}
