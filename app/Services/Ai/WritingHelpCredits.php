<?php

namespace App\Services\Ai;

use App\Enums\Feature;
use App\Enums\WritingHelpAction;
use App\Models\AppSetting;
use App\Models\Restaurant;
use App\Models\WritingHelpDay;
use Carbon\CarbonImmutable;

/**
 * A restaurant's AI credits a month. Its package gives an AI budget in
 * dollars (packages.ai_budget_cents), which is credits at what the admin
 * says a credit is worth; one restaurant may be given its own number or
 * extra (Feature::WritingHelpCredits grants). Each action costs its credits
 * (WritingHelpAction), they come back on the 1st in the restaurant's
 * timezone, and owners only ever see credits, never money or the model.
 *
 * Behind the credits a cap keeps the real cost to what they are worth: an
 * action runs only while this month's real cost plus the most it could
 * cost stays within the restaurant's credits times a credit's value, and
 * the credits left are never more than the money left buys.
 */
class WritingHelpCredits
{
    /** What a credit is worth until the admin says otherwise: a hundredth of a cent. */
    public const DEFAULT_CREDIT_CENTS = 0.01;

    /** What one credit is worth, in US cents. */
    public static function creditCents(): float
    {
        return max(0.0001, (float) AppSetting::read('ai_credit_cents', self::DEFAULT_CREDIT_CENTS));
    }

    public static function priceOf(WritingHelpAction $action): int
    {
        return max(0, (int) AppSetting::read("ai_credits_{$action->value}", $action->defaultCredits()));
    }

    /**
     * The credits a budget of $cents a month buys, at $creditCents a credit
     * (what a credit is worth now when not given); null (no cap) for none.
     */
    public static function forBudget(?float $cents, ?float $creditCents = null): ?int
    {
        return $cents === null ? null : (int) floor(round($cents / ($creditCents ?? self::creditCents()), 6));
    }

    /** What $credits are worth, in US dollars. */
    public static function dollarsOf(int $credits): float
    {
        return $credits * self::creditCents() / 100;
    }

    /** The credits a month, or null for no cap. */
    public function limit(Restaurant $restaurant): ?int
    {
        return $restaurant->entitlements()->limit(Feature::WritingHelpCredits);
    }

    /**
     * This month's credits and real cost, from the 1st in the restaurant's
     * timezone.
     *
     * @return array{credits: int, cost: float}
     */
    public function thisMonth(Restaurant $restaurant): array
    {
        $month = WritingHelpDay::query()
            ->where('restaurant_id', $restaurant->id)
            ->where('day', '>=', now($restaurant->localTimezone())->startOfMonth()->toDateString())
            ->selectRaw('COALESCE(SUM(credits), 0) AS credits, COALESCE(SUM(cost), 0) AS cost')
            ->first();

        return ['credits' => (int) $month?->getAttribute('credits'), 'cost' => (float) $month?->getAttribute('cost')];
    }

    /** What is left this month, or null for no cap. */
    public function left(Restaurant $restaurant): ?int
    {
        $limit = $this->limit($restaurant);

        return $limit === null ? null : self::leftOf($limit, $this->thisMonth($restaurant));
    }

    /**
     * What is left of $limit credits after $month: the credits not taken,
     * and never more than the money not spent buys, so the owner is never
     * shown credits the cap would refuse (a month of dearer requests than
     * their price spends the money first).
     *
     * @param  array{credits: int, cost: float}  $month
     */
    private static function leftOf(int $limit, array $month): int
    {
        $affordable = (int) floor(round((self::dollarsOf($limit) - $month['cost']) / self::dollarsOf(1), 6));

        return max(0, min($limit - $month['credits'], $affordable));
    }

    /** The day the credits come back: the 1st of next month, the restaurant's. */
    public function resetsOn(Restaurant $restaurant): CarbonImmutable
    {
        return CarbonImmutable::now($restaurant->localTimezone())->startOfMonth()->addMonth();
    }

    /**
     * Why $action may not run, null when it may: `credits` when fewer are
     * left than it costs, `cost` when they are but this month's real cost
     * plus the most it could cost ($mostItCouldCost, in dollars) would pass
     * what the credits are worth (a big request near the month's end).
     *
     * @return 'credits'|'cost'|null
     */
    public function refusal(Restaurant $restaurant, WritingHelpAction $action, float $mostItCouldCost): ?string
    {
        $limit = $this->limit($restaurant);

        if ($limit === null) {
            return null;
        }

        $month = $this->thisMonth($restaurant);

        return match (true) {
            self::leftOf($limit, $month) < $action->credits() => 'credits',
            self::dollarsOf($limit) < $month['cost'] + $mostItCouldCost => 'cost',
            default => null,
        };
    }

    /**
     * The month for the admin: credits a month (null for no cap), taken and
     * left, their real cost and what they are worth, in dollars.
     *
     * @return array{limit: int|null, used: int, left: int|null, cost: float, worth: float|null}
     */
    public function forAdmin(Restaurant $restaurant): array
    {
        $limit = $this->limit($restaurant);
        $month = $this->thisMonth($restaurant);

        return [
            'limit' => $limit,
            'used' => $month['credits'],
            'left' => $limit === null ? null : self::leftOf($limit, $month),
            'cost' => round($month['cost'], 6),
            'worth' => $limit === null ? null : round(self::dollarsOf($limit), 6),
        ];
    }

    /**
     * The credits for the dashboard: how many a month and left (null for no
     * cap), when they come back, and each action's price.
     *
     * @return array{limit: int|null, left: int|null, resets_on: string, prices: array<string, int>}
     */
    public function forDashboard(Restaurant $restaurant): array
    {
        return [
            'limit' => $this->limit($restaurant),
            'left' => $this->left($restaurant),
            'resets_on' => $this->resetsOn($restaurant)->toDateString(),
            'prices' => collect(WritingHelpAction::cases())
                ->mapWithKeys(fn (WritingHelpAction $action): array => [$action->value => $action->credits()])
                ->all(),
        ];
    }
}
