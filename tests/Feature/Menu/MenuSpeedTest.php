<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishBuild;
use App\Models\DishVariant;
use App\Models\DishVariantOption;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
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

    /**
     * What a guest's menu costs once the caches are warm, with every option
     * on (variants, add-ons, photos, links): 10 in October 2026.
     */
    private const MENU_QUERIES = 10;

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
        $media = $media->fresh();
        $this->assertStringEndsWith('?v='.$media->updated_at->timestamp, $media->getUrl('thumb'));
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

        $this->assertStringContainsString('class="dish-photo" src="'.$media->fresh()->getUrl().'"', $this->menu());
    }

    public function test_a_remade_card_picture_gets_a_new_address_so_caches_let_it_go(): void
    {
        $media = $this->dish->addMedia(UploadedFile::fake()->image('kafta.jpg', 1200, 900))->toMediaCollection('image');
        $before = $this->menu();

        $this->travel(1)->hour();
        $media->fresh()->touch();

        $this->assertStringContainsString('-thumb.webp?v=', $before);
        $this->assertNotSame(
            $this->photoAddress($before),
            $this->photoAddress($this->menu()),
            'The same address would keep showing the old picture for a week.',
        );
    }

    private function photoAddress(string $html): string
    {
        preg_match('/class="dish-photo" src="([^"]+)"/', $html, $match);

        return $match[1] ?? '';
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

    /** Categories of dishes, each with a size to pick, an add-on and a photo. */
    private function fillMenu(int $categories, int $dishesEach): void
    {
        foreach (range(1, $categories) as $ignored) {
            $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);

            foreach (range(1, $dishesEach) as $ignoredToo) {
                $dish = Dish::factory()->create(['restaurant_id' => $this->shop->id, 'category_id' => $category->id, 'price' => 5]);
                $variant = DishVariant::factory()->create(['dish_id' => $dish->id]);
                DishVariantOption::factory()->count(2)->create(['dish_variant_id' => $variant->id]);
                DishAddon::factory()->create(['dish_id' => $dish->id]);
                $dish->addMedia(UploadedFile::fake()->image('dish.jpg', 300, 200))->toMediaCollection('image');
            }
        }
        RestaurantSocialLink::factory()->create(['restaurant_id' => $this->shop->id]);
    }

    private function queriesForTheMenu(): int
    {
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });
        $this->menu();

        return $queries;
    }

    public function test_the_menu_costs_the_same_few_queries_however_many_dishes_it_has(): void
    {
        $this->defaultPackageIncludes(Feature::Variants, Feature::Addons, Feature::Ordering, Feature::MultipleLanguages);
        $this->fillMenu(1, 1);
        // The first view also fills the caches every later guest reads.
        $this->menu();
        $this->app->forgetScopedInstances();
        $small = $this->queriesForTheMenu();

        $this->fillMenu(4, 6);
        $this->app->forgetScopedInstances();
        $big = $this->queriesForTheMenu();

        $this->assertSame($small, $big, 'A query per dish, category or choice crept in.');
        $this->assertLessThanOrEqual(self::MENU_QUERIES, $big);
    }

    public function test_offers_deal_dishes_cost_no_query_each(): void
    {
        $this->defaultPackageIncludes(Feature::Offers, Feature::OfferSchedules);
        $addDeals = function (int $count): void {
            foreach (range(1, $count) as $n) {
                $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
                // Live, not live yet, and paused: drawn, drawn hidden, left out.
                foreach ([[], ['time_windows' => [['from' => '00:00', 'to' => '00:01']]], ['is_active' => false]] as $state) {
                    Dish::factory()->dealOf(Offer::factory()->create(['restaurant_id' => $this->shop->id, ...$state]))->create(['category_id' => $category->id]);
                }
            }
        };
        $addDeals(1);
        $this->menu();
        $this->app->forgetScopedInstances();
        $small = $this->queriesForTheMenu();

        $addDeals(4);
        $this->app->forgetScopedInstances();

        $this->assertSame($small, $this->queriesForTheMenu(), 'A query per deal dish or offer crept in.');
    }

    public function test_dishes_guests_build_are_read_with_the_rest(): void
    {
        $this->defaultPackageIncludes(Feature::BuildYourOwn, Feature::Variants, Feature::Addons);
        $addBuilds = function (int $count): void {
            foreach (range(1, $count) as $ignored) {
                $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
                Dish::factory()->count(2)->create(['restaurant_id' => $this->shop->id, 'category_id' => $category->id, 'price' => 5]);
                // One in no category, one still filed under a category.
                foreach ([null, $category->id] as $filed) {
                    $pizza = Dish::factory()->create(['restaurant_id' => $this->shop->id, 'category_id' => $filed, 'price' => null]);
                    DishBuild::factory()->for($pizza)->create(['category_id' => $category->id]);
                }
            }
        };
        $addBuilds(1);
        $this->menu();
        $this->app->forgetScopedInstances();
        $small = $this->queriesForTheMenu();

        $addBuilds(4);
        $this->app->forgetScopedInstances();

        $this->assertSame($small, $this->queriesForTheMenu(), 'A query per dish guests build crept in.');
        // The menu's dishes and the builds' are one read: 12 in October 2026.
        $this->assertLessThanOrEqual(12, $small);
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

    public function test_a_guest_costs_no_session_and_no_write(): void
    {
        // Nothing is stored for a guest opening the menu: no session (Redis),
        // no visit row (the page reports its own visit, later), no cookie.
        $response = $this->get(route('public.menu', $this->shop->slug))->assertOk();

        $response->assertCookieMissing((string) config('session.cookie'))->assertCookieMissing('XSRF-TOKEN');
        $this->assertSame(0, $this->shop->menuSessions()->count());
    }

    public function test_uploads_are_cached_for_a_year_and_text_is_compressed(): void
    {
        $this->assertSame('public, max-age=31536000, immutable', config('media-library.remote.extra_headers.CacheControl'));

        $htaccess = (string) file_get_contents(public_path('.htaccess'));
        $this->assertStringContainsString('AddOutputFilterByType DEFLATE text/html', $htaccess);
        $this->assertStringContainsString('Header set Cache-Control "public, max-age=31536000, immutable"', $htaccess);
    }
}
