<?php

namespace Tests\Integration\Database;

use App\Models\Dish;
use App\Models\MenuEvent;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Database\Seeders\TemplateSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\TestCase;

/**
 * The demo restaurant has everything a real one would, every picture made
 * by the upload pipeline (WebP, its preset's size), and seeding it again
 * starts it over rather than adding a second one (one test: each seed
 * makes thirty pictures).
 */
class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_demo_has_everything_filled_in_with_webp_pictures(): void
    {
        $this->seed(TemplateSeeder::class);
        $this->seed(DemoSeeder::class);

        $owner = User::query()->where('username', DemoSeeder::USERNAME)->firstOrFail();
        $this->assertTrue(Hash::check(DemoSeeder::PASSWORD, (string) $owner->password));
        $this->assertTrue($owner->hasCompletedOnboarding());

        $demo = Restaurant::query()->where('slug', DemoSeeder::SLUG)->firstOrFail();
        $this->assertSame($owner->id, $demo->user_id);
        $this->assertSame('premium', $demo->effectivePackage()?->slug);
        $this->assertSame('ar', $demo->second_locale);
        $this->assertNotNull($demo->template_id);

        // Every picture came out of the pipeline as WebP.
        $pictures = Media::query()->get();
        $this->assertNotEmpty($pictures);
        $this->assertSame(['image/webp'], $pictures->pluck('mime_type')->unique()->values()->all());
        $this->assertNotNull($demo->getFirstMedia('logo'));
        $this->assertNotNull($demo->getFirstMedia('cover_image'));

        $dishes = Dish::query()->where('restaurant_id', $demo->id)->with(['media', 'variants.options', 'addons'])->get();
        $this->assertSame(6, $demo->categories()->count());
        $this->assertGreaterThanOrEqual(25, $dishes->count());
        $this->assertGreaterThanOrEqual(20, $dishes->filter(fn (Dish $dish): bool => $dish->getFirstMedia('image') !== null)->count());
        $this->assertTrue($dishes->contains(fn (Dish $dish): bool => $dish->variants->isNotEmpty() && $dish->variants->first()->options->count() >= 2));
        $this->assertTrue($dishes->contains(fn (Dish $dish): bool => $dish->addons->isNotEmpty()));
        $this->assertTrue($dishes->contains(fn (Dish $dish): bool => ! $dish->is_available));
        // A dish keeps its photo's size, at most the dish preset's.
        $photo = $dishes->firstWhere('price', '5.50')->getFirstMedia('image');
        $this->assertLessThanOrEqual(1200, $photo->getCustomProperty('width'));

        $offers = Offer::query()->where('restaurant_id', $demo->id)->with(['media', 'dishes'])->get();
        $this->assertCount(5, $offers);
        $this->assertSame(
            ['ad', 'banner', 'popup'],
            $offers->flatMap(fn (Offer $offer): array => $offer->placements)->unique()->sort()->values()->all(),
        );
        $this->assertTrue($offers->every(fn (Offer $offer): bool => $offer->getFirstMedia('image') !== null));
        $this->assertTrue($offers->contains(fn (Offer $offer): bool => $offer->repeat->value === 'weekly'));
        $this->assertTrue($offers->contains(fn (Offer $offer): bool => $offer->discount_type->value === 'amount'));

        $this->assertSame(8, $demo->diningTables()->count());
        $this->assertSame(3, $demo->socialLinks()->count());

        $orders = Order::query()->where('restaurant_id', $demo->id)->withCount('items')->get();
        $this->assertCount(48, $orders);
        $this->assertTrue($orders->every(fn (Order $order): bool => $order->items_count > 0));
        $this->assertGreaterThan(500, $demo->menuSessions()->count());
        $this->assertGreaterThan(0, MenuEvent::query()->where('restaurant_id', $demo->id)->count());

        // The menu opens for guests.
        $this->get('/'.DemoSeeder::SLUG)->assertOk()->assertSee('Olive &amp; Ember', false);

        // Seeded again, it starts over: one demo, and the old pictures gone with it.
        $before = $pictures->count();
        $this->seed(DemoSeeder::class);
        $this->assertSame(1, User::query()->where('username', DemoSeeder::USERNAME)->count());
        $this->assertSame(1, Restaurant::query()->where('slug', DemoSeeder::SLUG)->count());
        $this->assertSame(48, Order::query()->count());
        $this->assertSame($before, Media::query()->count());
    }

    public function test_it_never_takes_another_restaurants_link(): void
    {
        $this->seed(TemplateSeeder::class);
        Restaurant::factory()->create(['slug' => DemoSeeder::SLUG]);

        $this->expectExceptionMessage('belongs to another restaurant');

        $this->seed(DemoSeeder::class);
    }
}
