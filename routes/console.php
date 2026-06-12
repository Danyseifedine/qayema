<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly analytics rollup (per-restaurant timezone buckets) + raw-row pruning.
Schedule::command('stats:rollup')->dailyAt('03:10');
