<?php

namespace Database\Seeders;

use App\Models\Feature;
use App\Models\Template;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Sellable units per the v2 spec: limit features replace the old
     * restaurants.*_limit columns and are bundled on the free template;
     * boolean add-ons are purchasable separately.
     */
    public function run(): void
    {
        $features = [
            ['slug' => 'dish_limit', 'name' => ['en' => 'Dish limit', 'ar' => 'حد الأطباق'], 'kind' => 'limit', 'is_addon' => false],
            ['slug' => 'category_limit', 'name' => ['en' => 'Category limit', 'ar' => 'حد الفئات'], 'kind' => 'limit', 'is_addon' => false],
            ['slug' => 'social_link_limit', 'name' => ['en' => 'Social link limit', 'ar' => 'حد روابط التواصل'], 'kind' => 'limit', 'is_addon' => false],
            ['slug' => 'ordering', 'name' => ['en' => 'Ordering', 'ar' => 'استقبال الطلبات'], 'kind' => 'boolean', 'is_addon' => true],
            ['slug' => 'remove_branding', 'name' => ['en' => 'Remove branding', 'ar' => 'إزالة العلامة التجارية'], 'kind' => 'boolean', 'is_addon' => true],
        ];

        foreach ($features as $data) {
            Feature::query()->updateOrCreate(['slug' => $data['slug']], $data);
        }

        // The free "default" template carries the baseline limits.
        $basic = Template::where('slug', 'default')->first();

        if ($basic) {
            $values = [
                'dish_limit' => '40',
                'category_limit' => '10',
                'social_link_limit' => '4',
            ];

            foreach ($values as $slug => $value) {
                $feature = Feature::where('slug', $slug)->first();

                if ($feature) {
                    $basic->features()->syncWithoutDetaching([$feature->id => ['value' => $value]]);
                }
            }
        }
    }
}
