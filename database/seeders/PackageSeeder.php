<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * The four packages are already inserted by their migration. Seeding only
     * fills a gap — `firstOrCreate`, never `updateOrCreate` — so running
     * `db:seed` on a live database cannot overwrite what an admin has edited.
     */
    public function run(): void
    {
        foreach (config('package.catalog', []) as $package) {
            Package::firstOrCreate(['slug' => $package['slug']], [
                'name' => $package['name'],
                'description' => $package['description'] ?? null,
                'price_cents' => $package['price_cents'] ?? null,
                'currency' => $package['currency'] ?? 'USD',
                'is_contact_only' => $package['is_contact_only'] ?? false,
                'is_default' => $package['is_default'] ?? false,
                'sort_order' => $package['sort_order'] ?? 0,
                'features' => $package['features'] ?? [],
            ]);
        }
    }
}
