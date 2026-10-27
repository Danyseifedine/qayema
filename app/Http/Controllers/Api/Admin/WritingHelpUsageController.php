<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WritingHelpUsageRequest;
use App\Models\Restaurant;
use App\Models\WritingHelpDay;
use App\Services\Ai\WritingHelpLog;
use App\Services\Ai\WritingHelpPrice;
use App\Services\Ai\WritingHelpSetup;
use App\Services\Menu\MenuLanguages;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;

/**
 * What the writing help (the dashboard's AI buttons) spent: today, over
 * the last WritingHelpLog::DAYS days or this month, in all and per
 * restaurant, the most tokens first, next to what each restaurant pays,
 * and the model it runs on with its price. A day is the admins' own, in
 * Lebanon; each restaurant's rows are its own days, so a restaurant far
 * away may be a few hours off.
 */
class WritingHelpUsageController extends Controller
{
    /** The restaurants the page lists. */
    public const LISTED = 100;

    public function __invoke(WritingHelpUsageRequest $request, WritingHelpLog $log): JsonResponse
    {
        $since = $request->since();
        $rows = $log->byRestaurantSince($since, self::LISTED)
            ->filter(fn (WritingHelpDay $row): bool => $row->restaurant !== null);
        // Which of them pay now, in one query, so each row's AI cost sits
        // next to what that restaurant pays a month.
        $paying = Restaurant::query()->paying()->whereKey($rows->pluck('restaurant_id'))->pluck('id')->flip();
        $price = WritingHelpPrice::perMillion();

        return response()->json(['data' => [
            'range' => $request->range(),
            'since' => $since,
            ...$log->totalsSince($since),
            'model' => [
                'name' => WritingHelpSetup::effectiveModel(),
                // Dollars per million tokens; null when no price is known.
                'input_per_million' => $price[0] ?? null,
                'output_per_million' => $price[1] ?? null,
            ],
            'by_restaurant' => $rows
                ->map(fn (WritingHelpDay $row): array => [
                    'restaurant' => [
                        'id' => $row->restaurant->id,
                        'name' => MenuLanguages::reader($row->restaurant, MenuLanguages::main($row->restaurant))($row->restaurant, 'name'),
                        'slug' => $row->restaurant->slug,
                        'logo_url' => MediaUrl::of($row->restaurant, 'logo'),
                    ],
                    'uses' => (int) $row->uses,
                    'input_tokens' => (int) $row->input_tokens,
                    'output_tokens' => (int) $row->output_tokens,
                    // In dollars, as each request was priced when it was made.
                    'cost' => round((float) $row->cost, 6),
                    // What it pays a month while it is paying (Restaurant::paying()),
                    // else null, in its package's currency.
                    'pays_cents' => $paying->has($row->restaurant->id) ? $row->restaurant->monthlyPriceCents() : null,
                    'currency' => $row->restaurant->effectivePackage()->currency ?? 'USD',
                ])
                ->values(),
        ]]);
    }
}
