<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Convert content columns to spatie/laravel-translatable JSON columns
     * ({"ar": ..., "en": ...}). Restaurant-owned content is backfilled under
     * the restaurant's default_locale; platform-owned content (tags,
     * restaurant_types, templates) under "en". Backfill runs in chunked PHP
     * so it works on both MySQL and the SQLite test database.
     *
     * @var array<string, array{columns: string[], locale: string}>
     */
    private array $tables = [
        'restaurants' => ['columns' => ['name', 'description', 'address'], 'locale' => 'own'],
        'categories' => ['columns' => ['name', 'description'], 'locale' => 'restaurant'],
        'dishes' => ['columns' => ['name', 'ingredients'], 'locale' => 'restaurant'],
        'tags' => ['columns' => ['name'], 'locale' => 'en'],
        'restaurant_types' => ['columns' => ['name'], 'locale' => 'en'],
        'templates' => ['columns' => ['name', 'description'], 'locale' => 'en'],
    ];

    public function up(): void
    {
        $restaurantLocales = DB::table('restaurants')->pluck('default_locale', 'id')
            ->map(fn ($locale) => in_array($locale, ['ar', 'en'], true) ? $locale : 'en');

        foreach ($this->tables as $tableName => $config) {
            foreach ($config['columns'] as $column) {
                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->json($column.'_i18n')->nullable();
                });

                DB::table($tableName)->whereNotNull($column)->orderBy('id')->chunkById(
                    200,
                    function ($rows) use ($tableName, $column, $config, $restaurantLocales) {
                        foreach ($rows as $row) {
                            $locale = match ($config['locale']) {
                                'own' => $restaurantLocales[$row->id] ?? 'en',
                                'restaurant' => $restaurantLocales[$row->restaurant_id] ?? 'en',
                                default => $config['locale'],
                            };

                            DB::table($tableName)->where('id', $row->id)->update([
                                $column.'_i18n' => json_encode([$locale => $row->{$column}], JSON_UNESCAPED_UNICODE),
                            ]);
                        }
                    }
                );

                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });

                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->renameColumn($column.'_i18n', $column);
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName => $config) {
            foreach ($config['columns'] as $column) {
                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->text($column.'_plain')->nullable();
                });

                DB::table($tableName)->whereNotNull($column)->orderBy('id')->chunkById(
                    200,
                    function ($rows) use ($tableName, $column) {
                        foreach ($rows as $row) {
                            $translations = json_decode($row->{$column} ?? '{}', true) ?: [];

                            DB::table($tableName)->where('id', $row->id)->update([
                                $column.'_plain' => $translations['en'] ?? $translations['ar'] ?? reset($translations) ?: null,
                            ]);
                        }
                    }
                );

                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->dropColumn($column);
                });

                Schema::table($tableName, function (Blueprint $table) use ($column) {
                    $table->renameColumn($column.'_plain', $column);
                });
            }
        }
    }
};
