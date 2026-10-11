<?php

namespace App\Services\Ai;

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

    /** One request: a use when it gave the owner text, and the tokens it spent either way. */
    public function record(Restaurant $restaurant, bool $used, int $inputTokens, int $outputTokens): void
    {
        if (! $used && $inputTokens === 0 && $outputTokens === 0) {
            return;
        }

        $now = now();

        WritingHelpDay::query()->upsert(
            [[
                'restaurant_id' => $restaurant->id,
                'day' => now($restaurant->localTimezone())->toDateString(),
                'uses' => (int) $used,
                'input_tokens' => $inputTokens,
                'output_tokens' => $outputTokens,
                'created_at' => $now,
                'updated_at' => $now,
            ]],
            ['restaurant_id', 'day'],
            [
                'uses' => DB::raw('uses + '.(int) $used),
                'input_tokens' => DB::raw('input_tokens + '.$inputTokens),
                'output_tokens' => DB::raw('output_tokens + '.$outputTokens),
                'updated_at' => $now,
            ],
        );
    }

    /**
     * One restaurant's use: today, and over the last DAYS days.
     *
     * @return array{today: int, uses: int, tokens: int}
     */
    public function of(Restaurant $restaurant): array
    {
        $today = now($restaurant->localTimezone())->toDateString();
        $days = WritingHelpDay::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereDate('day', '>', now($restaurant->localTimezone())->subDays(self::DAYS)->toDateString())
            ->get(['day', 'uses', 'input_tokens', 'output_tokens']);

        return [
            'today' => (int) $days->first(fn (WritingHelpDay $day): bool => $day->day->toDateString() === $today)?->uses,
            'uses' => (int) $days->sum('uses'),
            'tokens' => (int) $days->sum(fn (WritingHelpDay $day): int => $day->input_tokens + $day->output_tokens),
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
     * @return array{uses: int, tokens: int, restaurants: int}
     */
    public function overall(): array
    {
        $totals = WritingHelpDay::query()
            ->whereDate('day', '>', now()->subDays(self::DAYS)->toDateString())
            ->selectRaw('COALESCE(SUM(uses), 0) AS uses, COALESCE(SUM(input_tokens + output_tokens), 0) AS tokens, COUNT(DISTINCT restaurant_id) AS restaurants')
            ->first();

        return [
            'uses' => (int) $totals?->uses,
            'tokens' => (int) $totals?->tokens,
            'restaurants' => (int) $totals?->restaurants,
        ];
    }

    /**
     * Everyone's use from $day (a calendar day) through today, the tokens
     * in and out apart: the admin phone app's home and its AI page.
     *
     * @return array{uses: int, input_tokens: int, output_tokens: int, restaurants: int}
     */
    public function totalsSince(string $day): array
    {
        $totals = WritingHelpDay::query()
            ->whereDate('day', '>=', $day)
            ->selectRaw('COALESCE(SUM(uses), 0) AS uses, COALESCE(SUM(input_tokens), 0) AS input_tokens, COALESCE(SUM(output_tokens), 0) AS output_tokens, COUNT(DISTINCT restaurant_id) AS restaurants')
            ->first();

        return [
            'uses' => (int) $totals?->uses,
            'input_tokens' => (int) $totals?->input_tokens,
            'output_tokens' => (int) $totals?->output_tokens,
            'restaurants' => (int) $totals?->restaurants,
        ];
    }

    /**
     * Each restaurant's use from $day, the most tokens first, with the
     * restaurant and its logo: one query for the sums, then the restaurants.
     *
     * @return Collection<int, WritingHelpDay>
     */
    public function byRestaurantSince(string $day, int $limit): Collection
    {
        return WritingHelpDay::query()
            ->whereDate('day', '>=', $day)
            ->groupBy('restaurant_id')
            ->selectRaw('restaurant_id, SUM(uses) AS uses, SUM(input_tokens) AS input_tokens, SUM(output_tokens) AS output_tokens')
            ->orderByRaw('SUM(input_tokens + output_tokens) DESC')
            ->limit($limit)
            ->with('restaurant.media')
            ->get();
    }
}
