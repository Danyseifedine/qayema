<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Sellable units per the v2 spec: limit features replace the old
     * restaurants.*_limit columns and seed the per-restaurant default snapshot;
     * boolean add-ons are purchasable separately. Features are no longer bundled
     * on templates — the floor lives in package_defaults.
     */
    public function run(): void
    {
        $features = [
            ['slug' => 'dish_limit', 'name' => ['en' => 'Dish limit', 'ar' => 'حد الأطباق'], 'kind' => 'limit', 'is_addon' => false],
            ['slug' => 'category_limit', 'name' => ['en' => 'Category limit', 'ar' => 'حد الفئات'], 'kind' => 'limit', 'is_addon' => false],
            ['slug' => 'social_link_limit', 'name' => ['en' => 'Social link limit', 'ar' => 'حد روابط التواصل'], 'kind' => 'limit', 'is_addon' => false],
            ['slug' => 'ordering', 'name' => ['en' => 'Ordering', 'ar' => 'استقبال الطلبات'], 'kind' => 'boolean', 'is_addon' => true],
            ['slug' => 'remove_branding', 'name' => ['en' => 'Remove branding', 'ar' => 'إزالة العلامة التجارية'], 'kind' => 'boolean', 'is_addon' => true],
            ['slug' => 'qr_studio', 'name' => ['en' => 'QR Studio', 'ar' => 'استوديو QR'], 'kind' => 'boolean', 'is_addon' => true],
        ];

        foreach ($features as $data) {
            Feature::query()->updateOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
