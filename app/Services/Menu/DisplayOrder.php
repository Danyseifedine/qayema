<?php

namespace App\Services\Menu;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Writing a new `display_order` for a whole list at once.
 *
 * Reordering used to run one UPDATE per row, so dragging a dish on a menu of
 * three hundred cost three hundred round trips inside one transaction. This
 * builds a single `CASE` expression instead, so any list is one statement
 * (after one read of which ids the owner really has).
 */
class DisplayOrder
{
    /**
     * Ids the owner does not have (unknown, or another restaurant's) are
     * dropped before numbering, so a tampered payload touches nothing and
     * leaves no gap in the owner's order.
     *
     * @param  HasMany<covariant \Illuminate\Database\Eloquent\Model, covariant \Illuminate\Database\Eloquent\Model>  $relation  the owner's scope, which keeps a tampered id out
     * @param  array<int, int>  $ids  the ids to renumber, in their new order
     */
    public static function apply(HasMany $relation, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $owned = $relation->newQuery()->whereIn('id', $ids)->pluck('id')->flip();

        // Keep the client's order, drop anything it does not own. `flip()` makes
        // that check a hash lookup rather than a scan per id.
        $orderedIds = array_values(array_filter($ids, static fn (int|string $id): bool => $owned->has($id)));

        if ($orderedIds === []) {
            return;
        }

        $cases = [];

        foreach ($orderedIds as $index => $id) {
            // Both halves are cast to int here, so the expression carries no
            // caller-controlled text even though it is built as raw SQL:
            // `update()` gives a raw expression no bindings of its own.
            $cases[] = 'when '.(int) $id.' then '.($index + 1);
        }

        $relation->newQuery()
            // An UPDATE inherits the relation's ordering on MySQL, which is
            // pointless work when the order is exactly what is being rewritten.
            ->reorder()
            ->whereIn('id', $orderedIds)
            ->update(['display_order' => DB::raw('case id '.implode(' ', $cases).' end')]);

        // One statement saves no model, so ClearsMenuCache never hears it.
        if ($relation->getParent() instanceof Restaurant) {
            app(MenuCache::class)->forget($relation->getParent());
        }
    }
}
