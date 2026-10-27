<?php

use App\Services\Health\HealthChecks;
use Illuminate\Support\Facades\Schedule;

// Production's cron runs `php artisan schedule:run` every minute; everything
// the app needs done in the background is here, so it ships with the code.

// The heartbeat `/up` checks (HealthChecks): a scheduler that stopped shows.
Schedule::call(fn () => HealthChecks::beat())->everyMinute()->name('health:heartbeat');

// The queue worker (queued mail, order news): started
// every minute and kept up for most of it, so a job starts within seconds
// and a worker that died or grew is replaced the next minute, on the code
// deployed by then. Never two at once.
Schedule::command('queue:work --max-time=55 --sleep=3 --tries=3 --memory=128')
    ->everyMinute()
    ->withoutOverlapping(2)
    ->runInBackground();

// The Cloudflare purges it refused (PendingPurges), sent again in the five
// calls a minute its Free plan allows.
Schedule::command('menus:purge-pending')->everyMinute()->withoutOverlapping(2);

// Menus whose package or grant started or ended with the clock: Cloudflare's
// copies are cleared (ForgetEndedMenus).
Schedule::command('menus:forget-ended')->hourly();

// Each night, the finished days added up into menu_days, then the raw visits
// and events past 6 months deleted (never when adding up failed).
Schedule::command('stats:rollup')->dailyAt('03:10');

// What would otherwise grow for ever: failed jobs after 30 days, the phone
// apps' expired tokens, blocks that ended a week ago (BlockedIp::prunable).
Schedule::command('queue:prune-failed --hours=720')->dailyAt('03:30');
Schedule::command('sanctum:prune-expired --hours=48')->dailyAt('03:35');
Schedule::command('model:prune')->dailyAt('03:40');

// The admins' phones: the packages ending in the next 3 days, each morning
// in Lebanon.
Schedule::command('packages:ending-soon-admin-reminder')->dailyAt('09:00')->timezone(App\Services\Push\AdminAlerts::TIMEZONE);
