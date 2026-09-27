<?php

namespace App\Services\Analytics;

use App\Enums\MenuEventType;
use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Stores what guests did on a public menu, sent in batches by
 * public/js/menu-track.js.
 *
 * Nothing from the page is taken on trust: a dish or category that is not this
 * restaurant's drops the event, a field the type does not carry is cleared,
 * and a search term is normalised so "Pizza " and "pizza" count together.
 *
 * Best-effort, like MenuVisitRecorder: analytics must never be the reason a
 * guest's tap fails, so a failure here is swallowed.
 */
class MenuEventRecorder
{
    /**
     * @param  array<int, array{type: string, dish_id?: int|null, category_id?: int|null, value?: string|null}>  $events
     * @return int how many were stored
     */
    public function record(Restaurant $restaurant, Request $request, array $events): int
    {
        try {
            $session = $request->hasSession()
                ? $request->session()->getId()
                : md5($request->ip().$request->userAgent());

            $dishIds = $this->idsFor($events, 'dish_id', fn (array $ids) => $restaurant->dishes()->whereKey($ids)->pluck('dishes.id'));
            $categoryIds = $this->idsFor($events, 'category_id', fn (array $ids) => $restaurant->categories()->whereKey($ids)->pluck('id'));

            $now = now();
            $rows = [];

            foreach ($events as $event) {
                $type = MenuEventType::from($event['type']);
                $dish = $type->carriesDish() ? ($event['dish_id'] ?? null) : null;
                $category = $type->carriesCategory() ? ($event['category_id'] ?? null) : null;
                $value = $type->carriesValue() ? $this->clean($type, $event['value'] ?? null, $restaurant->menuLanguages()) : null;

                if ($type->carriesDish() && ! isset($dishIds[$dish])) {
                    continue;
                }

                if ($type->carriesCategory() && ! isset($categoryIds[$category])) {
                    continue;
                }

                if ($type->carriesValue() && $value === null) {
                    continue;
                }

                $rows[] = [
                    'restaurant_id' => $restaurant->id,
                    'session_id' => $session,
                    'type' => $type->value,
                    'dish_id' => $dish,
                    'category_id' => $category,
                    'value' => $value,
                    'occurred_at' => $now,
                ];
            }

            if ($rows !== []) {
                $restaurant->menuEvents()->insert($rows);
            }

            return count($rows);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * The ids among the events that are really this restaurant's, as a set.
     *
     * @param  array<int, array<string, mixed>>  $events
     * @param  callable(array<int, int>): iterable<int>  $lookup
     * @return array<int, true>
     */
    private function idsFor(array $events, string $key, callable $lookup): array
    {
        $asked = array_values(array_unique(array_filter(array_map(
            fn (array $event) => isset($event[$key]) ? (int) $event[$key] : null,
            $events,
        ))));

        if ($asked === []) {
            return [];
        }

        return array_fill_keys(collect($lookup($asked))->map(fn ($id) => (int) $id)->all(), true);
    }

    /**
     * @param  array<int, string>  $languages  the menu's own languages
     */
    private function clean(MenuEventType $type, ?string $value, array $languages): ?string
    {
        $value = Str::of((string) $value)->squish()->lower()->limit(64, '')->toString();

        if ($value === '') {
            return null;
        }

        // A language is only worth counting when it is one the menu offers.
        if ($type === MenuEventType::Language && ! in_array($value, $languages, true)) {
            return null;
        }

        // One or two letters is someone still typing, not a search.
        if (in_array($type, [MenuEventType::Search, MenuEventType::SearchMiss], true) && mb_strlen($value) < 2) {
            return null;
        }

        return $value;
    }
}
