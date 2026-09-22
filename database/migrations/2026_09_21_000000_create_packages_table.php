<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What a restaurant can be on. A package holds its own limits and flags in
     * `features` (a JSON map of App\Enums\Feature slug => int|null, where null
     * means unlimited and a flag is 0 or 1), so adding a tier is a row and
     * adding a feature is an enum case.
     *
     * Seeded from config('package.catalog') so a fresh database — and every
     * test run — has the four packages before anything else needs one.
     */
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 32)->unique();
            $table->json('name');
            $table->json('description')->nullable();
            // Null means the price is not published: the owner has to ask.
            $table->unsignedInteger('price_cents')->nullable();
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_contact_only')->default(false);
            // Exactly one row: what a new restaurant starts on, and what an
            // expired package falls back to.
            $table->boolean('is_default')->default(false);
            $table->integer('sort_order')->default(0);
            $table->json('features');
            $table->timestamps();
        });

        $now = now();

        DB::table('packages')->insert(
            collect(config('package.catalog', []))
                ->map(fn (array $package): array => [
                    'slug' => $package['slug'],
                    'name' => json_encode($package['name'], JSON_UNESCAPED_UNICODE),
                    'description' => json_encode($package['description'] ?? [], JSON_UNESCAPED_UNICODE),
                    'price_cents' => $package['price_cents'] ?? null,
                    'currency' => $package['currency'] ?? 'USD',
                    'is_contact_only' => $package['is_contact_only'] ?? false,
                    'is_default' => $package['is_default'] ?? false,
                    'sort_order' => $package['sort_order'] ?? 0,
                    'features' => json_encode($package['features'] ?? []),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
