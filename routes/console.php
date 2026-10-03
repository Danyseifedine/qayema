<?php

use Illuminate\Support\Facades\Schedule;

// Nightly pruning of raw menu_sessions rows past the retention window.
Schedule::command('stats:rollup')->dailyAt('03:10');

// Guests' phone numbers and addresses on orders older than 90 days.
Schedule::command('orders:forget-guests')->dailyAt('03:20');
