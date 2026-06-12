<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `restaurant_id` columns on categories and dishes were added via a raw
     * foreign key without an explicit index. Some drivers (e.g. MySQL) auto-create
     * one for the FK; others (e.g. SQLite) do not — so we add it only where missing.
     */
    public function up(): void
    {
        foreach (['categories', 'dishes'] as $table) {
            if (! Schema::hasIndex($table, ['restaurant_id'])) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->index('restaurant_id');
                });
            }
        }
    }

    public function down(): void
    {
        foreach (['categories', 'dishes'] as $table) {
            $indexName = $table.'_restaurant_id_index';

            if (Schema::hasIndex($table, $indexName)) {
                Schema::table($table, function (Blueprint $blueprint) use ($indexName): void {
                    $blueprint->dropIndex($indexName);
                });
            }
        }
    }
};
