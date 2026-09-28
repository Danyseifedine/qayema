<?php

use Illuminate\Support\Facades\Schedule;

// Nightly pruning of raw menu_sessions rows past the retention window.
Schedule::command('stats:rollup')->dailyAt('03:10');
