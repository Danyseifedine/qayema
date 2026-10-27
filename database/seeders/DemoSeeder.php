<?php

namespace Database\Seeders;

use App\Enums\Fulfilment;
use App\Enums\MenuEventType;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Dish;
use App\Models\MenuEvent;
use App\Models\MenuSession;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;
use App\Services\Media\MediaService;
use App\Services\Packages\PackageAssigner;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * A demo restaurant with everything filled in, to show Qayema or try it:
 * "Olive & Ember", a Lebanese grill in English and Arabic on Premium, with
 * a logo and a cover, six categories of dishes with photos, variants and
 * add-ons, banner, pop-up and ad offers (schedules and discounts), tables,
 * social links, orders of the last three weeks and a month of visits.
 *
 * Every picture goes through the upload pipeline (MediaService::storeUpload:
 * resized to its preset and turned into WebP) from `database/seeders/demo`,
 * CC0 and public-domain photos listed in `demo/sources.json`; the logo is
 * drawn for this demo. Run it again to start the demo over: the old demo
 * owner and restaurant go first, their pictures with them.
 *
 *   php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public const USERNAME = 'demo';

    public const EMAIL = 'demo@qayema.test';

    public const PASSWORD = 'qayema-demo';

    public const SLUG = 'demo';

    private const PICTURES = __DIR__.'/demo';

    public function __construct(
        private readonly MediaService $media,
        private readonly PackageAssigner $packages,
    ) {}

    public function run(): void
    {
        // The same demo every time it is seeded.
        mt_srand(2026);

        $this->removeOldDemo();

        $owner = User::query()->create([
            'name' => 'Demo Owner',
            'username' => self::USERNAME,
            'email' => self::EMAIL,
            'password' => Hash::make(self::PASSWORD),
            'role' => UserRole::MenuOwner,
            'onboarding_step' => User::ONBOARDING_STEPS,
            'onboarding_completed_at' => now(),
        ]);

        $restaurant = $this->restaurant($owner);
        $dishes = $this->menu($restaurant);
        $offers = $this->offers($restaurant, $dishes);
        $this->socialLinks($restaurant);
        $tables = $this->tables($restaurant);
        $this->orders($restaurant, $dishes, $tables);
        $this->visits($restaurant, $dishes, $offers);

        $this->command->info('Demo ready at /'.self::SLUG.': sign in as "'.self::USERNAME.'" with "'.self::PASSWORD.'".');
    }

    /** The demo of a previous seed, through the models so its pictures leave storage. */
    private function removeOldDemo(): void
    {
        $taken = Restaurant::query()->where('slug', self::SLUG)->with('user')->first();
        if ($taken !== null && $taken->user?->username !== self::USERNAME) {
            throw new RuntimeException('The link /'.self::SLUG.' belongs to another restaurant; the demo needs it.');
        }

        User::query()
            ->where('username', self::USERNAME)
            ->orWhere('email', self::EMAIL)
            ->get()
            ->each->delete();
    }

    private function restaurant(User $owner): Restaurant
    {
        $classic = Template::query()->where('slug', 'classic')->first();

        $restaurant = Restaurant::query()->create([
            'user_id' => $owner->id,
            'name' => ['en' => 'Olive & Ember', 'ar' => 'زيتون وجمر'],
            'description' => [
                'en' => 'Charcoal grills, warm mezze and fresh drinks in the heart of Hamra.',
                'ar' => 'مشاوي على الفحم، مقبلات دافئة ومشروبات طازجة في قلب الحمرا.',
            ],
            'slug' => self::SLUG,
            'template_id' => $classic?->id,
            'template_settings' => $classic === null ? null : [$classic->id => ['primary_color' => '#C8963E']],
            'is_active' => true,
            'country_code' => 'LB',
            'phone' => '+9611000000',
            'currency' => 'USD',
            'timezone' => 'Asia/Beirut',
            'google_maps_url' => 'https://www.google.com/maps?q=33.8963,35.4823',
            'main_locale' => 'en',
            'default_locale' => 'en',
            'second_locale' => 'ar',
            'opening_hours' => [
                'mon' => [['open' => '12:00', 'close' => '23:00']],
                'tue' => [['open' => '12:00', 'close' => '23:00']],
                'wed' => [['open' => '12:00', 'close' => '23:00']],
                'thu' => [['open' => '12:00', 'close' => '23:30']],
                'fri' => [['open' => '12:00', 'close' => '01:00']],
                'sat' => [['open' => '12:00', 'close' => '01:00']],
                'sun' => [['open' => '13:00', 'close' => '22:00']],
            ],
            'qr_settings' => [
                'dot_style' => 'rounded',
                'dot_color' => '#2B2118',
                'corner_style' => 'extra-rounded',
                'corner_color' => '#C8963E',
                'eye_style' => 'dot',
                'eye_color' => '#C8963E',
                'background' => '#FFFFFF',
                'logo' => true,
                'logo_size' => 'medium',
                'card_theme' => 'dark',
                'subtitle' => 'Grill & mezze',
                'cta' => 'Scan to see the menu',
            ],
            'order_mode' => 'menu',
            'order_types' => ['delivery', 'pickup'],
            'dine_in_mode' => 'menu',
        ]);

        $premium = Package::findBySlug('premium') ?? Package::default();
        $this->packages->assign($restaurant, $premium, now()->subMonth(), null, 'Demo account');
        $restaurant->update(['switched_off' => []]);

        $this->picture($restaurant, 'logo.png', 'logo', 'logo');
        $this->picture($restaurant, 'cover.jpg', 'cover_image', 'cover_image');

        return $restaurant;
    }

    /**
     * The categories and their dishes, keyed by the dish's picture name.
     *
     * @return Collection<string, Dish>
     */
    private function menu(Restaurant $restaurant): Collection
    {
        $dishes = collect();

        foreach (self::MENU as $position => $section) {
            $category = Category::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $section['name'],
                'description' => $section['description'],
                'icon' => $section['icon'],
                'display_order' => $position,
            ]);

            foreach ($section['dishes'] as $order => $row) {
                $dish = Dish::query()->create([
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $category->id,
                    'name' => $row['name'],
                    'ingredients' => $row['ingredients'] ?? null,
                    'price' => $row['price'],
                    'is_available' => $row['available'] ?? true,
                    'display_order' => $order,
                ]);

                foreach ($row['variants'] ?? [] as $index => $variant) {
                    $saved = $dish->variants()->create(['name' => $variant['name'], 'display_order' => $index]);
                    foreach ($variant['options'] as $at => [$name, $price]) {
                        $saved->options()->create(['name' => $name, 'price' => $price, 'display_order' => $at]);
                    }
                }
                foreach ($row['addons'] ?? [] as $index => [$name, $price]) {
                    $dish->addons()->create(['name' => $name, 'price' => $price, 'display_order' => $index]);
                }

                if (isset($row['photo'])) {
                    $this->picture($dish, $row['photo'].'.jpg', 'dish', 'image');
                }

                $dishes[$row['key']] = $dish;
            }
        }

        return $dishes;
    }

    /**
     * Three banners, a pop-up and an ad: schedules, a percent and an amount off.
     *
     * @param  Collection<string, Dish>  $dishes
     * @return list<Offer>
     */
    private function offers(Restaurant $restaurant, Collection $dishes): array
    {
        $ids = fn (string ...$keys): array => array_map(fn (string $key): int => $dishes[$key]->id, $keys);
        $rows = [
            [
                'picture' => 'offer-grill.jpg',
                'dishes' => $ids('mixed-grill', 'shish-tawook', 'kafta', 'lamb-chops', 'grilled-chicken'),
                'title' => ['en' => 'Grill nights', 'ar' => 'ليالي المشاوي'],
                'description' => ['en' => '15% off every grill, Thursday to Saturday evenings.', 'ar' => 'خصم ١٥٪ على كل المشاوي، من الخميس إلى السبت مساءً.'],
                'placements' => ['banner'],
                'banner_shape' => 'rectangle',
                'action' => 'dishes',
                'discount_type' => 'percent',
                'discount_value' => 15,
                'repeat' => 'weekly',
                'weekdays' => ['thu', 'fri', 'sat'],
                'time_windows' => [['from' => '18:00', 'to' => '23:00']],
            ],
            [
                'picture' => 'offer-drinks.jpg',
                'dishes' => $ids('lemonade', 'orange-juice', 'iced-coffee'),
                'title' => ['en' => 'Happy hour', 'ar' => 'ساعة السعادة'],
                'description' => ['en' => '20% off fresh drinks, every day from 4 to 7.', 'ar' => 'خصم ٢٠٪ على المشروبات الطازجة، كل يوم من ٤ إلى ٧.'],
                'placements' => ['banner'],
                'banner_shape' => 'square',
                'action' => 'dishes',
                'discount_type' => 'percent',
                'discount_value' => 20,
                'repeat' => 'always',
                'time_windows' => [['from' => '16:00', 'to' => '19:00']],
            ],
            [
                'picture' => 'offer-burger.jpg',
                'dishes' => $ids('cheeseburger'),
                'title' => ['en' => 'New: the double cheeseburger', 'ar' => 'جديد: دبل تشيز برغر'],
                'description' => ['en' => 'Two patties, two slices of cheese, our own sauce.', 'ar' => 'قطعتا لحم، شريحتا جبنة، وصلصتنا الخاصة.'],
                'placements' => ['banner'],
                'banner_shape' => 'rectangle',
                'action' => 'dishes',
                'discount_type' => 'none',
                'repeat' => 'always',
            ],
            [
                'picture' => 'offer-dessert.jpg',
                'dishes' => $ids('baklava', 'chocolate-cake', 'rice-pudding'),
                'title' => ['en' => 'A sweet ending', 'ar' => 'ختامها حلو'],
                'description' => ['en' => '$1.50 off any dessert today.', 'ar' => 'خصم ١٫٥٠ دولار على أي حلوى اليوم.'],
                'placements' => ['popup'],
                'popup_shape' => 'tall',
                'action' => 'dishes',
                'show_again' => 'daily',
                'delay_seconds' => 2,
                'discount_type' => 'amount',
                'discount_value' => 1.5,
                'repeat' => 'always',
            ],
            [
                'picture' => 'offer-lemonade.jpg',
                'dishes' => $ids('lemonade'),
                'title' => ['en' => 'Try our mint lemonade', 'ar' => 'جرّب ليموناضة النعناع'],
                'description' => ['en' => 'Squeezed to order, with fresh mint.', 'ar' => 'تُعصر عند الطلب، مع نعناع طازج.'],
                'placements' => ['ad'],
                'action' => 'dishes',
                'ad_seconds' => 6,
                'ad_delay_seconds' => 3,
                'discount_type' => 'none',
                'repeat' => 'always',
                'ends_at' => now()->addDays(14),
            ],
        ];

        $offers = [];
        foreach ($rows as $order => $row) {
            $offer = Offer::query()->create([
                'restaurant_id' => $restaurant->id,
                'display_order' => $order,
                'is_active' => true,
                ...collect($row)->except(['picture', 'dishes'])->all(),
            ]);
            $offer->dishes()->sync($row['dishes']);
            $this->picture($offer, $row['picture'], 'offer', 'image');
            $offers[] = $offer;
        }

        return $offers;
    }

    private function socialLinks(Restaurant $restaurant): void
    {
        foreach ([
            'instagram' => 'https://www.instagram.com/oliveandember.demo',
            'facebook' => 'https://www.facebook.com/oliveandember.demo',
            'tiktok' => 'https://www.tiktok.com/@oliveandember.demo',
        ] as $platform => $url) {
            $restaurant->socialLinks()->create(['platform' => $platform, 'url' => $url]);
        }
    }

    /** @return list<DiningTable> */
    private function tables(Restaurant $restaurant): array
    {
        $names = ['Table 1', 'Table 2', 'Table 3', 'Table 4', 'Table 5', 'Table 6', 'Terrace 1', 'Terrace 2'];
        $codes = DiningTable::freshCodes(count($names));

        return array_map(fn (string $name, int $index): DiningTable => $restaurant->diningTables()->create([
            'name' => $name,
            'sort_order' => $index,
            'code' => $codes[$index],
        ]), $names, array_keys($names));
    }

    /**
     * Three weeks of orders: delivery, pickup and at the table, most done, a
     * few cancelled, and today's still being made.
     *
     * @param  Collection<string, Dish>  $dishes
     * @param  list<DiningTable>  $tables
     */
    private function orders(Restaurant $restaurant, Collection $dishes, array $tables): void
    {
        $guests = ['Rami Haddad', 'Lea Khoury', 'Karim Nassar', 'Maya Saad', 'Omar Fares', 'Nour Aziz', 'Jad Mansour', 'Sara Hamdan'];
        $addresses = ['Hamra, Bliss Street, 3rd floor', 'Ras Beirut, Makdessi Street', 'Verdun, near the mall', 'Achrafieh, Sassine Square'];
        $sold = $dishes->filter(fn (Dish $dish): bool => $dish->is_available && $dish->price !== null)->values();
        $now = CarbonImmutable::now();

        for ($i = 0; $i < 48; $i++) {
            // Six of today's still open; the rest spread over three weeks.
            $placed = $i < 6
                ? $now->subMinutes(8 + $i * 17)
                : $now->subDays(mt_rand(1, 21))->setTime(mt_rand(12, 22), mt_rand(0, 59));
            $status = match (true) {
                $i < 2 => OrderStatus::Placed,
                $i < 4 => OrderStatus::Accepted,
                $i < 6 => OrderStatus::Ready,
                mt_rand(1, 100) <= 12 => OrderStatus::Cancelled,
                default => OrderStatus::Done,
            };
            $fulfilment = [Fulfilment::Delivery, Fulfilment::Delivery, Fulfilment::Pickup, Fulfilment::DineIn][mt_rand(0, 3)];
            $table = $fulfilment === Fulfilment::DineIn ? $tables[mt_rand(0, count($tables) - 1)] : null;

            $lines = [];
            foreach ((array) array_rand($sold->all(), mt_rand(1, 4)) as $index) {
                $dish = $sold[$index];
                $quantity = mt_rand(1, 3);
                $lines[] = [
                    'dish_id' => $dish->id,
                    'name' => $dish->getTranslation('name', 'en'),
                    'unit_price' => $dish->price,
                    'quantity' => $quantity,
                    'line_total' => bcmul((string) $dish->price, (string) $quantity, 2),
                ];
            }

            $order = Order::query()->create([
                'restaurant_id' => $restaurant->id,
                'reference' => Order::newReference(),
                'status' => $status,
                'channel' => OrderChannel::Menu,
                'fulfilment' => $fulfilment,
                'table_id' => $table?->id,
                'table_name' => $table?->name,
                'currency' => $restaurant->currency,
                'total' => array_reduce($lines, fn (string $sum, array $line): string => bcadd($sum, $line['line_total'], 2), '0'),
                'note' => $i % 7 === 0 ? 'No onions, please.' : null,
                'guest_name' => $table === null ? $guests[mt_rand(0, count($guests) - 1)] : null,
                'guest_phone' => $table === null ? '+96170000'.str_pad((string) mt_rand(0, 999), 3, '0', STR_PAD_LEFT) : null,
                'address' => $fulfilment === Fulfilment::Delivery ? $addresses[mt_rand(0, count($addresses) - 1)] : null,
                'client_token' => (string) Str::uuid(),
                'tracking_token' => Str::random(40),
                'placed_at' => $placed,
                'accepted_at' => $status === OrderStatus::Placed || $status === OrderStatus::Cancelled ? null : $placed->addMinutes(4),
                'ready_at' => in_array($status, [OrderStatus::Ready, OrderStatus::Done], true) ? $placed->addMinutes(22) : null,
                'closed_at' => in_array($status, [OrderStatus::Done, OrderStatus::Cancelled], true) ? $placed->addMinutes(40) : null,
            ]);
            $order->addItems($lines);
        }
    }

    /**
     * A month of visits and what guests did on the menu, for Analytics:
     * written in bulk, as the menu's own reports would have left them.
     *
     * @param  Collection<string, Dish>  $dishes
     * @param  list<Offer>  $offers
     */
    private function visits(Restaurant $restaurant, Collection $dishes, array $offers): void
    {
        $categories = $restaurant->categories()->pluck('id')->all();
        $dishIds = $dishes->pluck('id')->all();
        $searches = ['shawarma', 'burger', 'hummus', 'lemonade', 'pizza', 'vegan', 'coffee', 'sushi'];
        $devices = [['mobile', 'iOS', 'Safari'], ['mobile', 'Android', 'Chrome'], ['mobile', 'Android', 'Chrome'], ['mobile', 'iOS', 'Safari'], ['desktop', 'Windows', 'Chrome'], ['desktop', 'macOS', 'Safari'], ['tablet', 'iOS', 'Safari']];
        $sessions = [];
        $events = [];
        $now = CarbonImmutable::now();

        for ($day = 29; $day >= 0; $day--) {
            // Busier at the weekend.
            $date = $now->subDays($day);
            $count = mt_rand(25, 45) + ($date->isWeekend() ? 20 : 0);

            for ($n = 0; $n < $count; $n++) {
                $at = $date->setTime(mt_rand(11, 23), mt_rand(0, 59), mt_rand(0, 59));
                if ($at->isFuture()) {
                    continue;
                }
                // Some guests come back: the same id on several days.
                $visitor = 'demo-'.mt_rand(1, 500);
                [$device, $os, $browser] = $devices[mt_rand(0, count($devices) - 1)];
                $sessions[] = [
                    'restaurant_id' => $restaurant->id,
                    'session_id' => $visitor,
                    'device_type' => $device,
                    'browser' => $browser,
                    'os' => $os,
                    'locale' => mt_rand(1, 100) <= 60 ? 'en' : 'ar',
                    'viewed_at' => $at,
                    'via_qr' => mt_rand(1, 100) <= 45,
                    'created_at' => $at,
                    'updated_at' => $at,
                ];

                $event = fn (MenuEventType $type, array $about = []): array => [
                    'restaurant_id' => $restaurant->id,
                    'session_id' => $visitor,
                    'type' => $type->value,
                    'dish_id' => $about['dish_id'] ?? null,
                    'category_id' => $about['category_id'] ?? null,
                    'value' => $about['value'] ?? null,
                    'occurred_at' => $at->addSeconds(mt_rand(5, 300)),
                ];
                for ($k = mt_rand(1, 3); $k > 0; $k--) {
                    $events[] = $event(MenuEventType::CategoryOpen, ['category_id' => $categories[mt_rand(0, count($categories) - 1)]]);
                }
                for ($k = mt_rand(0, 2); $k > 0; $k--) {
                    $events[] = $event(MenuEventType::DishAdd, ['dish_id' => $dishIds[mt_rand(0, count($dishIds) - 1)]]);
                }
                $roll = mt_rand(1, 100);
                if ($roll <= 10) {
                    $term = $searches[mt_rand(0, count($searches) - 1)];
                    $events[] = $event($term === 'sushi' || $term === 'vegan' ? MenuEventType::SearchMiss : MenuEventType::Search, ['value' => $term]);
                }
                if ($roll <= 35) {
                    $offer = $offers[mt_rand(0, count($offers) - 1)];
                    $events[] = $event(MenuEventType::OfferShown, ['value' => (string) $offer->id]);
                    if ($roll <= 9) {
                        $events[] = $event(MenuEventType::OfferTap, ['value' => (string) $offer->id]);
                    }
                }
                match (true) {
                    $roll > 95 => $events[] = $event(MenuEventType::Map),
                    $roll > 92 => $events[] = $event(MenuEventType::Call),
                    $roll > 88 => $events[] = $event(MenuEventType::Social, ['value' => 'instagram']),
                    $roll > 80 => $events[] = $event(MenuEventType::Language, ['value' => 'ar']),
                    default => null,
                };
            }
        }

        foreach (array_chunk($sessions, 500) as $chunk) {
            MenuSession::query()->insert($chunk);
        }
        foreach (array_chunk($events, 500) as $chunk) {
            MenuEvent::query()->insert($chunk);
        }
    }

    /** A picture from `demo/`, through the same pipeline as an upload: its preset, then WebP. */
    private function picture(\Spatie\MediaLibrary\HasMedia $model, string $file, string $context, string $collection): void
    {
        $path = self::PICTURES.'/'.$file;
        $this->media->storeUpload($model, new UploadedFile($path, $file, null, null, true), $context, $collection);
    }

    /**
     * The menu: each dish's `key` names it for the offers and orders, and
     * `photo` its picture in `demo/` (a dish without one shows how the menu
     * looks without a photo).
     *
     * @var list<array{name: array<string, string>, description: array<string, string>, icon: string, dishes: list<array<string, mixed>>}>
     */
    private const MENU = [
        [
            'name' => ['en' => 'Mezze', 'ar' => 'مقبلات'],
            'icon' => 'salad',
            'description' => ['en' => 'Small plates to share, with warm bread.', 'ar' => 'أطباق صغيرة للمشاركة، مع خبز ساخن.'],
            'dishes' => [
                ['key' => 'hummus', 'photo' => 'hummus', 'price' => '5.50', 'name' => ['en' => 'Hummus', 'ar' => 'حمص'],
                    'ingredients' => ['en' => 'Chickpeas, tahini, lemon, olive oil', 'ar' => 'حمص، طحينة، ليمون، زيت زيتون'],
                    'addons' => [[['en' => 'Pine nuts', 'ar' => 'صنوبر'], '1.50'], [['en' => 'Spiced meat', 'ar' => 'لحمة مبهّرة'], '2.50']]],
                ['key' => 'falafel', 'photo' => 'falafel', 'price' => '6.00', 'name' => ['en' => 'Falafel', 'ar' => 'فلافل'],
                    'ingredients' => ['en' => 'Fava beans, chickpeas, herbs, tahini sauce', 'ar' => 'فول، حمص، أعشاب، صلصة طحينة'],
                    'variants' => [['name' => ['en' => 'Pieces', 'ar' => 'العدد'], 'options' => [[['en' => '6 pieces', 'ar' => '٦ قطع'], '0'], [['en' => '12 pieces', 'ar' => '١٢ قطعة'], '4.00']]]]],
                ['key' => 'tabbouleh', 'photo' => 'tabbouleh', 'price' => '6.50', 'name' => ['en' => 'Tabbouleh', 'ar' => 'تبولة'],
                    'ingredients' => ['en' => 'Parsley, bulgur, tomato, mint, lemon', 'ar' => 'بقدونس، برغل، بندورة، نعناع، ليمون']],
                ['key' => 'garden-salad', 'photo' => 'garden-salad', 'price' => '5.00', 'name' => ['en' => 'Garden salad', 'ar' => 'سلطة خضراء'],
                    'ingredients' => ['en' => 'Lettuce, beetroot, carrot, lemon dressing', 'ar' => 'خس، شمندر، جزر، صلصة الليمون']],
                ['key' => 'fries', 'photo' => 'fries', 'price' => '3.50', 'name' => ['en' => 'Crispy fries', 'ar' => 'بطاطا مقلية'],
                    'addons' => [[['en' => 'Garlic sauce', 'ar' => 'ثوم'], '0.50'], [['en' => 'Cheese sauce', 'ar' => 'صلصة جبنة'], '1.00']]],
            ],
        ],
        [
            'name' => ['en' => 'Charcoal grills', 'ar' => 'مشاوي على الفحم'],
            'icon' => 'grill',
            'description' => ['en' => 'Grilled to order, served with fries and garlic.', 'ar' => 'تُشوى عند الطلب، مع بطاطا وثوم.'],
            'dishes' => [
                ['key' => 'mixed-grill', 'photo' => 'mixed-grill', 'price' => '24.00', 'name' => ['en' => 'Mixed grill', 'ar' => 'مشاوي مشكّلة'],
                    'ingredients' => ['en' => 'Kafta, shish tawook, lamb cubes, grilled vegetables', 'ar' => 'كفتة، شيش طاووق، لحم غنم، خضار مشوية'],
                    'variants' => [['name' => ['en' => 'Size', 'ar' => 'الحجم'], 'options' => [[['en' => 'For one', 'ar' => 'لشخص'], '0'], [['en' => 'For two', 'ar' => 'لشخصين'], '18.00']]]]],
                ['key' => 'shish-tawook', 'photo' => 'shish-tawook', 'price' => '12.00', 'name' => ['en' => 'Shish tawook', 'ar' => 'شيش طاووق'],
                    'ingredients' => ['en' => 'Marinated chicken, garlic, lemon', 'ar' => 'دجاج متبّل، ثوم، ليمون'],
                    'addons' => [[['en' => 'Extra garlic', 'ar' => 'ثوم إضافي'], '0.50'], [['en' => 'Extra fries', 'ar' => 'بطاطا إضافية'], '2.00']]],
                ['key' => 'kafta', 'photo' => 'kafta', 'price' => '11.00', 'name' => ['en' => 'Kafta', 'ar' => 'كفتة'],
                    'ingredients' => ['en' => 'Minced lamb, parsley, onion', 'ar' => 'لحم غنم مفروم، بقدونس، بصل']],
                ['key' => 'lamb-chops', 'photo' => 'lamb-chops', 'price' => '19.00', 'name' => ['en' => 'Lamb chops', 'ar' => 'ريش غنم'],
                    'ingredients' => ['en' => 'Lamb chops, rosemary, sea salt', 'ar' => 'ريش غنم، إكليل الجبل، ملح بحري'],
                    'variants' => [['name' => ['en' => 'Cooked', 'ar' => 'الاستواء'], 'options' => [[['en' => 'Medium', 'ar' => 'وسط'], '0'], [['en' => 'Well done', 'ar' => 'مستوي جيداً'], '0']]]]],
                ['key' => 'grilled-chicken', 'photo' => 'grilled-chicken', 'price' => '13.50', 'name' => ['en' => 'Grilled chicken with rice', 'ar' => 'دجاج مشوي مع أرز'],
                    'ingredients' => ['en' => 'Half chicken, spiced rice, garlic', 'ar' => 'نصف دجاجة، أرز مبهّر، ثوم']],
            ],
        ],
        [
            'name' => ['en' => 'Shawarma & burgers', 'ar' => 'شاورما وبرغر'],
            'icon' => 'burger',
            'description' => ['en' => 'In a wrap, on a plate or in a bun.', 'ar' => 'بالخبز، بالصحن أو بالبرغر.'],
            'dishes' => [
                ['key' => 'shawarma-wrap', 'photo' => 'shawarma-wrap', 'price' => '7.00', 'name' => ['en' => 'Chicken shawarma wrap', 'ar' => 'سندويش شاورما دجاج'],
                    'ingredients' => ['en' => 'Chicken, garlic, pickles, fries', 'ar' => 'دجاج، ثوم، كبيس، بطاطا'],
                    'variants' => [['name' => ['en' => 'Size', 'ar' => 'الحجم'], 'options' => [[['en' => 'Regular', 'ar' => 'عادي'], '0'], [['en' => 'Large', 'ar' => 'كبير'], '2.00']]]],
                    'addons' => [[['en' => 'Extra garlic', 'ar' => 'ثوم إضافي'], '0.50'], [['en' => 'Cheese', 'ar' => 'جبنة'], '1.00']]],
                ['key' => 'chicken-shawarma-plate', 'photo' => 'chicken-shawarma-plate', 'price' => '11.00', 'name' => ['en' => 'Chicken shawarma plate', 'ar' => 'صحن شاورما دجاج'],
                    'ingredients' => ['en' => 'Chicken, rice, salad, garlic', 'ar' => 'دجاج، أرز، سلطة، ثوم']],
                ['key' => 'beef-shawarma-plate', 'photo' => 'beef-shawarma-plate', 'price' => '12.50', 'name' => ['en' => 'Beef shawarma plate', 'ar' => 'صحن شاورما لحمة'],
                    'ingredients' => ['en' => 'Beef, tahini, fries, salad', 'ar' => 'لحمة، طحينة، بطاطا، سلطة']],
                ['key' => 'burger', 'photo' => 'burger', 'price' => '10.00', 'name' => ['en' => 'Classic beef burger', 'ar' => 'برغر لحم كلاسيك'],
                    'ingredients' => ['en' => 'Beef patty, lettuce, tomato, house sauce', 'ar' => 'قطعة لحم، خس، بندورة، صلصة خاصة'],
                    'variants' => [['name' => ['en' => 'Cooked', 'ar' => 'الاستواء'], 'options' => [[['en' => 'Medium', 'ar' => 'وسط'], '0'], [['en' => 'Well done', 'ar' => 'مستوي جيداً'], '0']]]],
                    'addons' => [[['en' => 'Cheese', 'ar' => 'جبنة'], '1.00'], [['en' => 'Extra patty', 'ar' => 'قطعة لحم إضافية'], '4.00'], [['en' => 'Caramelised onions', 'ar' => 'بصل مكرمل'], '0.75']]],
                ['key' => 'cheeseburger', 'photo' => 'cheeseburger', 'price' => '13.00', 'name' => ['en' => 'Double cheeseburger', 'ar' => 'دبل تشيز برغر'],
                    'ingredients' => ['en' => 'Two beef patties, cheddar, pickles, house sauce', 'ar' => 'قطعتا لحم، شيدر، كبيس، صلصة خاصة']],
            ],
        ],
        [
            'name' => ['en' => 'Pizza', 'ar' => 'بيتزا'],
            'icon' => 'pizza',
            'description' => ['en' => 'Stone-baked, thin and crisp.', 'ar' => 'بالفرن الحجري، رقيقة ومقرمشة.'],
            'dishes' => [
                ['key' => 'margherita', 'photo' => 'margherita', 'price' => '11.00', 'name' => ['en' => 'Margherita', 'ar' => 'مارغريتا'],
                    'ingredients' => ['en' => 'Tomato, mozzarella, basil', 'ar' => 'بندورة، موزاريلا، حبق'],
                    'variants' => [['name' => ['en' => 'Size', 'ar' => 'الحجم'], 'options' => [[['en' => 'Medium', 'ar' => 'وسط'], '0'], [['en' => 'Large', 'ar' => 'كبير'], '4.00']]]]],
                ['key' => 'veggie-pizza', 'photo' => 'veggie-pizza', 'price' => '12.50', 'name' => ['en' => 'Garden pizza', 'ar' => 'بيتزا خضار'],
                    'ingredients' => ['en' => 'Peppers, mushrooms, corn, olives, mozzarella', 'ar' => 'فليفلة، فطر، ذرة، زيتون، موزاريلا']],
            ],
        ],
        [
            'name' => ['en' => 'Desserts', 'ar' => 'حلويات'],
            'icon' => 'cake',
            'description' => ['en' => 'Something sweet to finish.', 'ar' => 'شيء حلو للختام.'],
            'dishes' => [
                ['key' => 'baklava', 'photo' => 'baklava', 'price' => '6.00', 'name' => ['en' => 'Baklava', 'ar' => 'بقلاوة'],
                    'ingredients' => ['en' => 'Filo, pistachios, syrup', 'ar' => 'رقائق العجين، فستق حلبي، قطر']],
                ['key' => 'chocolate-cake', 'photo' => 'chocolate-cake', 'price' => '6.50', 'name' => ['en' => 'Chocolate cake', 'ar' => 'كيك الشوكولا'],
                    'addons' => [[['en' => 'Vanilla ice cream', 'ar' => 'بوظة فانيلا'], '1.50']]],
                ['key' => 'rice-pudding', 'price' => '4.50', 'name' => ['en' => 'Rice pudding', 'ar' => 'رز بحليب'],
                    'ingredients' => ['en' => 'Rice, milk, orange blossom, pistachios', 'ar' => 'أرز، حليب، ماء زهر، فستق حلبي']],
            ],
        ],
        [
            'name' => ['en' => 'Drinks', 'ar' => 'مشروبات'],
            'icon' => 'glass-full',
            'description' => ['en' => 'Fresh juices, coffee and more.', 'ar' => 'عصائر طازجة، قهوة والمزيد.'],
            'dishes' => [
                ['key' => 'lemonade', 'photo' => 'lemonade', 'price' => '4.00', 'name' => ['en' => 'Mint lemonade', 'ar' => 'ليموناضة بالنعناع'],
                    'variants' => [['name' => ['en' => 'Size', 'ar' => 'الحجم'], 'options' => [[['en' => 'Small', 'ar' => 'صغير'], '0'], [['en' => 'Large', 'ar' => 'كبير'], '1.50']]]]],
                ['key' => 'orange-juice', 'photo' => 'orange-juice', 'price' => '4.50', 'name' => ['en' => 'Fresh orange juice', 'ar' => 'عصير برتقال طازج']],
                ['key' => 'turkish-coffee', 'photo' => 'turkish-coffee', 'price' => '2.50', 'name' => ['en' => 'Turkish coffee', 'ar' => 'قهوة تركية'],
                    'variants' => [['name' => ['en' => 'Sugar', 'ar' => 'السكر'], 'options' => [[['en' => 'No sugar', 'ar' => 'سادة'], '0'], [['en' => 'Medium', 'ar' => 'وسط'], '0'], [['en' => 'Sweet', 'ar' => 'حلوة'], '0']]]]],
                ['key' => 'iced-coffee', 'photo' => 'iced-coffee', 'price' => '4.50', 'name' => ['en' => 'Iced coffee', 'ar' => 'قهوة مثلّجة'],
                    'addons' => [[['en' => 'Extra shot', 'ar' => 'جرعة إضافية'], '1.00'], [['en' => 'Oat milk', 'ar' => 'حليب الشوفان'], '0.75']]],
                ['key' => 'sparkling-water', 'price' => '1.50', 'name' => ['en' => 'Sparkling water', 'ar' => 'مياه غازية']],
                ['key' => 'carrot-juice', 'price' => '4.00', 'available' => false, 'name' => ['en' => 'Fresh carrot juice', 'ar' => 'عصير جزر طازج']],
            ],
        ],
    ];
}
