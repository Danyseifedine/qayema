<?php

namespace App\Services\Ai;

use App\Enums\WritingHelpAction;
use App\Models\Restaurant;
use App\Models\WritingHelpDay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * What the writing help cost, a row per restaurant per day of its own
 * (writing_help_days): written once a request by PaceWritingHelp, read by
 * the admin, so a jump in use shows before the provider's bill does.
 */
class WritingHelpLog
{
    /** The window the admin's totals cover. */
    public const DAYS = 30;

    /**
     * One request: a use and its $credits when it gave the owner text, and
     * the tokens it spent either way, counted under its $action too.
     */
    public function record(Restaurant $restaurant, bool $used, int $inputTokens, int $outputTokens, ?WritingHelpAction $action = null, int $credits = 0): void
    {
        if (! $used && $inputTokens === 0 && $outputTokens === 0) {
            return;
        }

        $now = now();
        // Priced now, at the model's price now: a later change of model
        // never re-prices the past.
        $cost = sprintf('%.6F', WritingHelpPrice::of($inputTokens, $outputTokens));
        $credits = $used ? $credits : 0;
        $kind = fn (WritingHelpAction $of): array => $action === $of ? [(int) $used, $cost] : [0, '0'];
        [$translations, $translationCost] = $kind(WritingHelpAction::Translation);
        [$descriptions, $descriptionCost] = $kind(WritingHelpAction::Description);

        WritingHelpDay::query()->upsert(
            [[
                'restaurant_id' => $restaurant->id,
                'day' => now($restaurant->localTimezone())->toDateString(),
                'uses' => (int) $used,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'cost' => $cost,
                'credits' => $credits,
                'translation_uses' => $translations,
                'translation_cost' => $translationCost,
                'description_uses' => $descriptions,
                'description_cost' => $descriptionCost,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['restaurant_id', 'day'],
            [
                'uses' => DB::raw('uses + '.(int) $used),
                'input_tokens' => DB::raw('input_tokens + '.$inputTokens),
                'output_tokens' => DB::raw('output_tokens + '.$outputTokens),
                'cost' => DB::raw('cost + '.$cost),
                'credits' => DB::raw('credits + '.$credits),
                'translation_uses' => DB::raw('translation_uses + '.$translations),
                'translation_cost' => DB::raw('translation_cost + '.$translationCost),
                'description_uses' => DB::raw('description_uses + '.$descriptions),
                'description_cost' => DB::raw('description_cost + '.$descriptionCost),
                'updated_at' => $now,
            ],
        );
    }

    /**
     * One restaurant's use: today, and over the last DAYS days.
     *
     * @return array{today: int, uses: int, tokens: int, cost: float}
     */
    public function of(Restaurant $restaurant): array
    {
        $today = now($restaurant->localTimezone())->toDateString();
        $days = WritingHelpDay::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('day', '>', now($restaurant->localTimezone())->subDays(self::DAYS)->toDateString())
            ->get(['day', 'uses', 'input_tokens', 'output_tokens', 'cost']);

        return [
            'today' => (int) $days->first(fn (WritingHelpDay $day): bool => $day->day->toDateString() === $today)?->uses,
            'uses' => (int) $days->sum('uses'),
            'tokens' => (int) $days->sum(fn (WritingHelpDay $day): int => $day->input_tokens + $day->output_tokens),
            'cost' => round((float) $days->sum(fn (WritingHelpDay $day): float => (float) $day->cost), 6),
        ];
    }

    /**
     * A restaurant's sum of $expression (a column of writing_help_days, or
     * columns added up) over the last DAYS days, as a subquery a list of
     * restaurants selects: the admin's Restaurants table reads every row's
     * use with the page, never a query per row.
     */
    public static function lastDaysOf(string $expression): Builder
    {
        return WritingHelpDay::query()
            ->selectRaw("COALESCE(SUM({$expression}), 0)")
            ->whereColumn('writing_help_days.restaurant_id', 'restaurants.id')
            ->whereDate('day', '>', now()->subDays(self::DAYS)->toDateString());
    }

    /**
     * Everyone's use over the last DAYS days, for the admin's dashboard.
     *
     * Today is the server's calendar day (each restaurant's own day is
     * close to it).
     *
     * The credits they took, and each kind of action's uses and real cost,
     * are what the admin checks the credits' prices against.
     *
     * @return array{uses: int, tokens: int, restaurants: int, cost: float, cost_today: float, credits: int, translation_uses: int, translation_cost: float, description_uses: int, description_cost: float}
     */
    public function overall(): array
    {
        $today = now()->toDateString();
        $totals = WritingHelpDay::query()
            ->whereDate('day', '>', now()->subDays(self::DAYS)->toDateString())
            ->selectRaw('COALESCE(SUM(uses), 0) AS uses, COALESCE(SUM(input_tokens + output_tokens), 0) AS tokens, COUNT(DISTINCT restaurant_id) AS restaurants, COALESCE(SUM(cost), 0) AS cost, COALESCE(SUM(CASE WHEN day = ? THEN cost ELSE 0 END), 0) AS cost_today', [$today])
            ->selectRaw('COALESCE(SUM(credits), 0) AS credits, COALESCE(SUM(translation_uses), 0) AS translation_uses, COALESCE(SUM(translation_cost), 0) AS translation_cost, COALESCE(SUM(description_uses), 0) AS description_uses, COALESCE(SUM(description_cost), 0) AS description_cost')
            ->first();

        return [
            'uses' => (int) $totals?->uses,
            'tokens' => (int) $totals?->tokens,
            'restaurants' => (int) $totals?->restaurants,
            'cost' => round((float) $totals?->getAttribute('cost'), 6),
            'cost_today' => round((float) $totals?->getAttribute('cost_today'), 6),
            'credits' => (int) $totals?->getAttribute('credits'),
            'translation_uses' => (int) $totals?->getAttribute('translation_uses'),
            'translation_cost' => round((float) $totals?->getAttribute('translation_cost'), 6),
            'description_uses' => (int) $totals?->getAttribute('description_uses'),
            'description_cost' => round((float) $totals?->getAttribute('description_cost'), 6),
        ];
    }

    /**
     * Everyone's use from $day (a calendar day) through today, the tokens
     * in and out apart, with the credits taken and each kind of action's
     * uses and cost: the admin phone app's home and its AI page.
     *
     * @return array{uses: int, input_tokens: int, output_tokens: int, restaurants: int, cost: float, credits: int, translation_uses: int, translation_cost: float, description_uses: int, description_cost: float}
     */
    public function totalsSince(string $day): array
    {
        $totals = WritingHelpDay::query()
            ->whereDate('day', '>=', $day)
            ->selectRaw('COALESCE(SUM(uses), 0) AS uses, COALESCE(SUM(input_tokens), 0) AS input_tokens, COALESCE(SUM(output_tokens), 0) AS output_tokens, COUNT(DISTINCT restaurant_id) AS restaurants, COALESCE(SUM(cost), 0) AS cost')
            ->selectRaw('COALESCE(SUM(credits), 0) AS credits, COALESCE(SUM(translation_uses), 0) AS translation_uses, COALESCE(SUM(translation_cost), 0) AS translation_cost, COALESCE(SUM(description_uses), 0) AS description_uses, COALESCE(SUM(description_cost), 0) AS description_cost')
            ->first();

        return [
            'uses' => (int) $totals?->uses,
            'input_tokens' => (int) $totals?->input_tokens,
            'output_tokens' => (int) $totals?->output_tokens,
            'restaurants' => (int) $totals?->restaurants,
            'cost' => round((float) $totals?->getAttribute('cost'), 6),
            'credits' => (int) $totals?->getAttribute('credits'),
            'translation_uses' => (int) $totals?->getAttribute('translation_uses'),
            'translation_cost' => round((float) $totals?->getAttribute('translation_cost'), 6),
            'description_uses' => (int) $totals?->getAttribute('description_uses'),
            'description_cost' => round((float) $totals?->getAttribute('description_cost'), 6),
        ];
    }

    /**
     * Each restaurant's use from $day, the most tokens first, with the
     * restaurant, its logo and its package (what it pays): one query for
     * the sums, then the restaurants.
     *
     * @return Collection<int, WritingHelpDay>
     */
    public function byRestaurantSince(string $day, int $limit): Collection
    {
        return WritingHelpDay::query()
            ->whereDate('day', '>=', $day)
            ->groupBy('restaurant_id')
            ->selectRaw('restaurant_id, SUM(uses) AS uses, SUM(input_tokens) AS input_tokens, SUM(output_tokens) AS output_tokens, SUM(cost) AS cost')
            ->orderByRaw('SUM(input_tokens + output_tokens) DESC')
            ->limit($limit)
            ->with(['restaurant.media', 'restaurant.package'])
            ->get();
    }
}
