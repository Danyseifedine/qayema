<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WritingHelpUsageRequest;
use App\Models\WritingHelpDay;
use App\Services\Ai\WritingHelpLog;
use App\Services\Menu\MenuLanguages;
use App\Services\Push\AdminAlerts;
use App\Support\MediaUrl;
use Illuminate\Http\JsonResponse;

/**
 * What the writing help (the dashboard's AI buttons) spent: today or over
 * the last WritingHelpLog::DAYS days, in all and per restaurant, the most
 * tokens first. A day is the admins' own, in Lebanon; each restaurant's
 * rows are its own days, so a restaurant far away may be a few hours off.
 */
class WritingHelpUsageController extends Controller
{
    /** The restaurants the page lists. */
    public const LISTED = 100;

    public function __invoke(WritingHelpUsageRequest $request, WritingHelpLog $log): JsonResponse
    {
        $range = $request->range();
        $today = now(AdminAlerts::TIMEZONE);
        $since = ($range === WritingHelpUsageRequest::TODAY ? $today : $today->subDays(WritingHelpLog::DAYS - 1))->toDateString();

        return response()->json(['data' => [
            'range' => $range,
            'since' => $since,
            ...$log->totalsSince($since),
            'by_restaurant' => $log->byRestaurantSince($since, self::LISTED)
                ->filter(fn (WritingHelpDay $row): bool => $row->restaurant !== null)
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
                ])
                ->values(),
        ]]);
    }
}
