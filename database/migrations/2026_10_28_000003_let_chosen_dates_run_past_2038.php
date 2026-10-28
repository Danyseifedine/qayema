<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dates someone picks for the future were MySQL `timestamp` columns, which
 * stop at 2038-01-19: an offer until 2040, a package extended past 2038 or a
 * ban until then failed to save. `datetime` holds any year. The app writes
 * and reads UTC either way, and MySQL converts each value as it changes the
 * type, so every date reads back as it did.
 *
 * Going down fails rather than cut a date past 2038.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> */
    private const COLUMNS = [
        'offers' => ['starts_at', 'ends_at'],
        'restaurants' => ['package_started_at', 'package_ends_at'],
        'feature_grants' => ['ends_at'],
        'package_changes' => ['starts_at', 'ends_at'],
        'blocked_ips' => ['expires_at'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->dateTime($column)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $name => $columns) {
            Schema::table($name, function (Blueprint $table) use ($columns) {
                foreach ($columns as $column) {
                    $table->timestamp($column)->nullable()->change();
                }
            });
        }
    }
};
