<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Services\MenuOwner\StatisticsQueryService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StatisticController extends Controller
{
    public function __construct(private readonly StatisticsQueryService $statistics) {}

    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;
        $period = $request->query('period');

        $payload = $restaurant
            ? $this->statistics->build($restaurant, is_string($period) ? $period : null)
            : $this->statistics->emptyPayload();

        return view('dashboard.statistics.index', $payload);
    }
}
