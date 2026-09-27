<?php

namespace App\Services\Menu;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Writing a new `display_order` for a whole list at once.
 *
 * Reordering used to run one UPDATE per row, so dragging a dish on a menu of
 * three hundred cost three hundred round trips inside one transaction. This
 * builds a single `CASE` expression instead, so any list is one statement.
 */
class DisplayOrder
{
    /**
     * @param  HasMany<covariant \Illuminate\Database\Eloquent\Model, \Illuminate\Database\Eloquent\Model>  $relation  the owner's scope, which keeps a tampered id out
     * @param  array<int, int>  $orderedIds  the ids to renumber, in their new order
     * @param  array<string, mixed>  $extra  columns to set on the same rows
     */
    public static function apply(HasMany $relation, array $orderedIds, array $extra = []): void
    {
        $orderedIds = array_values($orderedIds);

        if ($orderedIds === []) {
            return;
        }

        $cases = [];

        foreach ($orderedIds as $index => $id) {
            // Both halves are cast to int here, so the expression carries no
            // caller-controlled text even though it is built as raw SQL —
            // `update()` gives a raw expression no bindings of its own.
            $cases[] = 'when '.(int) $id.' then '.($index + 1);
        }

        $relation->newQuery()
            // An UPDATE inherits the relation's ordering on MySQL, which is
            // pointless work when the order is exactly what is being rewritten.
            ->reorder()
            ->whereIn('id', $orderedIds)
            ->update([...$extra, 'display_order' => DB::raw('case id '.implode(' ', $cases).' end')]);
    }
}
