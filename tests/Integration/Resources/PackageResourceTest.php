<?php

namespace Tests\Integration\Resources;

use App\Enums\Feature;
use App\Enums\FeatureKind;
use App\Http\Resources\PackageResource;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class PackageResourceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array<string, mixed> */
    private function resolve(Package $package): array
    {
        return (new PackageResource($package))->resolve(Request::create('/api/packages'));
    }

    public function test_the_free_package_in_full(): void
    {
        $package = Package::findBySlug('free');

        $this->assertSame([
            'id' => $package->id,
            'slug' => 'free',
            'name' => ['en' => 'Free', 'ar' => 'مجاني'],
            'description' => [
                'en' => 'Everything you need to take one menu live.',
                'ar' => 'كل ما تحتاجه لإطلاق قائمة واحدة.',
            ],
            'price_cents' => 0,
            'currency' => 'USD',
            'is_contact_only' => false,
            'is_default' => true,
            'is_featured' => false,
            'features' => [
                'dish_limit' => 40,
                'category_limit' => 8,
                'social_link_limit' => 1,
                'multiple_languages' => false,
                'variants' => false,
                'addons' => false,
                'appearance' => false,
                'premium_designs' => false,
                'qr_studio' => false,
                'ordering' => false,
                'analytics' => false,
                'advanced_analytics' => false,
            ],
        ], $this->resolve($package));
    }

    /** Custom: no published price, unlimited limits as null, every flag on. */
    public function test_the_custom_package_sends_nulls_for_unlimited_and_no_price(): void
    {
        $data = $this->resolve(Package::findBySlug('custom'));

        $this->assertNull($data['price_cents']);
        $this->assertTrue($data['is_contact_only']);
        $this->assertFalse($data['is_default']);
        $this->assertNull($data['features']['dish_limit']);
        $this->assertNull($data['features']['category_limit']);
        $this->assertNull($data['features']['social_link_limit']);
        foreach (Feature::flags() as $flag) {
            $this->assertTrue($data['features'][$flag->value], $flag->value);
        }
    }

    public function test_premium_is_the_featured_one(): void
    {
        $this->assertTrue($this->resolve(Package::findBySlug('premium'))['is_featured']);
        $this->assertFalse($this->resolve(Package::findBySlug('pro'))['is_featured']);
    }

    /** Every Feature case is a key, flags always booleans, limits always int or null. */
    public function test_every_feature_is_listed_with_the_type_its_kind_needs(): void
    {
        foreach (Package::query()->get() as $package) {
            $features = $this->resolve($package)['features'];

            $this->assertSame(array_column(Feature::cases(), 'value'), array_keys($features));
            foreach (Feature::cases() as $feature) {
                $value = $features[$feature->value];
                $feature->kind() === FeatureKind::Flag
                    ? $this->assertIsBool($value)
                    : $this->assertTrue($value === null || is_int($value), "{$package->slug}.{$feature->value}");
            }
        }
    }

    /** A flag stored as null (unlimited) reads as on; a key it lacks reads as the default. */
    public function test_odd_stored_values(): void
    {
        $package = Package::findBySlug('free');
        $package->features = ['qr_studio' => null, 'ordering' => 3];
        $package->replaceTranslations('name', ['en' => 'Free']);
        $package->replaceTranslations('description', []);

        $data = $this->resolve($package);

        $this->assertTrue($data['features']['qr_studio']);
        $this->assertTrue($data['features']['ordering']);
        $this->assertSame(Feature::DishLimit->defaultValue(), $data['features']['dish_limit']);
        $this->assertSame(Feature::Analytics->defaultValue() > 0, $data['features']['analytics']);
        $this->assertSame(['en' => 'Free', 'ar' => null], $data['name']);
        $this->assertSame(['en' => null, 'ar' => null], $data['description']);
    }
}
