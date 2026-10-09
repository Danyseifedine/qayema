<?php

namespace Tests\Feature\Menu;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What keeps the public menu quick to open: small pictures where small is
 * enough, the first picture asked for first, nothing that blocks the first
 * paint, and as little work as possible before the page is sent.
 */
class MenuSpeedTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Dish $dish;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        $this->shop = $this->published(['slug' => 'olive']);
        $this->dish = Dish::factory()->create([
            'restaurant_id' => $this->shop->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $this->shop->id])->id,
            'name' => ['en' => 'Kafta'],
        ]);
    }

    private function menu(): string
    {
        return (string) $this->get(route('public.menu', $this->shop->slug))->assertOk()->getContent();
    }

    public function test_a_dish_photo_gets_a_small_version_for_the_card_and_keeps_the_full_one_for_its_sheet(): void
    {
        $media = $this->dish->addMedia(UploadedFile::fake()->image('kafta.jpg', 1200, 900))->toMediaCollection('image');

        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        [$width, $height] = getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot('thumb')));
        // The whole photo in its own shape: shrunk, never cropped square.
        $this->assertSame([240, 180], [$width, $height]);

        $html = $this->menu();
        $this->assertStringContainsString('class="dish-photo" src="'.$media->getUrl('thumb').'"', $html);
        $this->assertStringContainsString('width="72" height="72"', $html);
        $this->assertStringContainsString('data-image="'.$media->getUrl('thumb').'" data-photo="'.$media->getUrl().'"', $html);
    }

    public function test_a_long_photo_keeps_both_ends_on_the_card(): void
    {
        $media = $this->dish->addMedia(UploadedFile::fake()->image('sub.jpg', 1200, 400))->toMediaCollection('image');

        [$width, $height] = getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot('thumb')));
        $this->assertSame([240, 80], [$width, $height]);
    }

    public function test_a_small_photo_is_never_enlarged_for_the_card(): void
    {
        $media = $this->dish->addMedia(UploadedFile::fake()->image('tiny.jpg', 100, 60))->toMediaCollection('image');

        [$width, $height] = getimagesize(Storage::disk('public')->path($media->getPathRelativeToRoot('thumb')));
        $this->assertSame([100, 60], [$width, $height]);
    }

    public function test_a_photo_from_before_the_small_versions_still_shows(): void
    {
        $media = $this->dish->addMedia(UploadedFile::fake()->image('kafta.jpg'))->toMediaCollection('image');
        // As a photo stored before conversions existed: none generated.
        Media::query()->whereKey($media->id)->update(['generated_conversions' => json_encode([])]);

        $this->assertStringContainsString('class="dish-photo" src="'.$media->getUrl().'"', $this->menu());
    }

    public function test_the_cover_is_asked_for_first_and_a_phone_gets_its_smaller_version(): void
    {
        $cover = $this->shop->addMedia(UploadedFile::fake()->image('cover.jpg', 1920, 600))->toMediaCollection('cover_image');

        $this->assertTrue($cover->hasGeneratedConversion('phone'));
        $html = $this->menu();

        $this->assertMatchesRegularExpression('/<img class="cover-photo"[^>]*fetchpriority="high"/', $html);
        $this->assertDoesNotMatchRegularExpression('/<img class="cover-photo"[^>]*loading="lazy"/', $html);
        $this->assertStringContainsString('srcset="'.$cover->getUrl('phone').' 960w, '.$cover->getUrl().' 1920w"', $html);
    }

    public function test_the_fonts_never_hold_the_first_paint(): void
    {
        $html = $this->menu();

        $this->assertMatchesRegularExpression('/<link rel="stylesheet" href="https:\/\/fonts\.googleapis\.com[^"]+" media="print" onload="this\.media=\'all\'">/', $html);
        $this->assertStringContainsString('<link rel="preload" as="style" href="https://fonts.googleapis.com', $html);
        $this->assertStringContainsString('&amp;display=swap', $html);
    }

    public function test_every_script_and_stylesheet_carries_its_version(): void
    {
        preg_match_all('/(?:src|href|data-lib)="([^"]+\.(?:js|css)[^"]*)"/', $this->menu(), $matches);

        $local = array_filter($matches[1], fn (string $url): bool => str_starts_with($url, url('/')));
        $this->assertNotEmpty($local);
        foreach ($local as $url) {
            $this->assertStringContainsString('?v=', $url, "{$url} would be kept a year without a version.");
        }
    }

    public function test_the_package_is_read_once_per_view(): void
    {
        $reads = 0;
        DB::listen(function ($query) use (&$reads): void {
            if (collect($query->bindings)->contains(fn ($binding): bool => is_string($binding) && str_contains($binding, 'entitlements:'))) {
                $reads++;
            }
        });

        $this->menu();

        $this->assertLessThanOrEqual(1, $reads);
    }

    public function test_the_visit_is_written_after_the_page(): void
    {
        $this->withDefer();
        $atResponse = null;
        Event::listen(RequestHandled::class, function () use (&$atResponse): void {
            $atResponse = $this->shop->menuSessions()->count();
        });

        $this->get(route('public.menu', $this->shop->slug))->assertOk();

        $this->assertSame(0, $atResponse, 'Nothing written before the page went out.');
        $this->assertSame(1, $this->shop->menuSessions()->count());
    }

    public function test_uploads_are_cached_for_a_year_and_text_is_compressed(): void
    {
        $this->assertSame('public, max-age=31536000, immutable', config('media-library.remote.extra_headers.CacheControl'));

        $htaccess = (string) file_get_contents(public_path('.htaccess'));
        $this->assertStringContainsString('AddOutputFilterByType DEFLATE text/html', $htaccess);
        $this->assertStringContainsString('Header set Cache-Control "public, max-age=31536000, immutable"', $htaccess);
    }
}
