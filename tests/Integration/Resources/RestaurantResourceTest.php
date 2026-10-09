<?php

namespace Tests\Integration\Resources;

use App\Enums\Feature;
use App\Http\Resources\RestaurantResource;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class RestaurantResourceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array<string, mixed> */
    private function resolve(Restaurant $restaurant): array
    {
        return (new RestaurantResource($restaurant))->resolve(Request::create('/api/restaurant'));
    }

    public function test_the_full_shape(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $restaurant = $this->owner([
            'name' => ['en' => 'Aran', 'ar' => 'آران'],
            'description' => ['en' => 'Levantine kitchen'],
            'slug' => 'aran',
            'second_locale' => 'ar',
            'google_maps_url' => 'https://maps.google.com/?q=33.89,35.50',
            'phone' => '70123456',
            'country_code' => 'LB',
            'currency' => 'USD',
            'opening_hours' => ['mon' => ['open' => '09:00', 'close' => '17:00']],
            'timezone' => 'Asia/Beirut',
        ]);

        $this->assertSame([
            'languages' => ['en', 'ar'],
            'name' => ['en' => 'Aran', 'ar' => 'آران'],
            'description' => ['en' => 'Levantine kitchen', 'ar' => null],
            'slug' => 'aran',
            'google_maps_url' => 'https://maps.google.com/?q=33.89,35.50',
            'phone' => '70123456',
            'country_code' => 'LB',
            'currency' => 'USD',
            // Saved before shifts: one range reads as one shift.
            'opening_hours' => [
                'mon' => [['open' => '09:00', 'close' => '17:00']],
                'tue' => null, 'wed' => null, 'thu' => null, 'fri' => null, 'sat' => null, 'sun' => null,
            ],
            'timezone' => 'Asia/Beirut',
            'logo_url' => null,
            'cover_url' => null,
        ], $this->resolve($restaurant));
    }

    /** Text follows the restaurant's own languages, which the package can narrow to English. */
    public function test_a_single_language_package_sends_english_only(): void
    {
        $restaurant = $this->owner(['name' => ['en' => 'Aran', 'ar' => 'آران'], 'second_locale' => 'ar']);

        $data = $this->resolve($restaurant);

        $this->assertSame(['en'], $data['languages']);
        $this->assertSame(['en' => 'Aran'], $data['name']);
    }

    public function test_the_blanks(): void
    {
        config(['app.timezone' => 'UTC']);
        $restaurant = $this->owner([
            'description' => null,
            'google_maps_url' => null,
            'opening_hours' => null,
            'timezone' => null,
            'second_locale' => null,
        ]);

        $data = $this->resolve($restaurant);

        $this->assertSame(['en' => null], $data['description']);
        $this->assertNull($data['google_maps_url']);
        $this->assertSame(array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], null), $data['opening_hours']);
        $this->assertSame('UTC', $data['timezone']);
    }

    public function test_the_logo_and_cover_come_as_urls(): void
    {
        $restaurant = $this->owner();
        $restaurant->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');
        $restaurant->addMedia(UploadedFile::fake()->image('cover.jpg'))->toMediaCollection('cover_image');

        $data = $this->resolve($restaurant->fresh());

        // Versioned (media-library.version_urls), so a remade image is fetched again.
        $this->assertMatchesRegularExpression('/logo\.png\?v=\d+$/', $data['logo_url']);
        // Versioned (media-library.version_urls), so a remade image is fetched again.
        $this->assertMatchesRegularExpression('/cover\.jpg\?v=\d+$/', $data['cover_url']);
    }
}
