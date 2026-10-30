<?php

namespace Tests\E2e;

use App\Enums\GrantMode;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\Order;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;
use App\Models\WritingHelpDay;
use App\Services\Packages\PackageAssigner;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Test-only endpoints for the Playwright suite (tests/E2e/routes.php, loaded
 * in the `e2e` environment only). Each spec builds the owner it needs here, so
 * specs share nothing and can run side by side.
 */
class E2eController extends Controller
{
    /**
     * Build an owner and everything they need.
     *
     * Body (all optional): package (free|pro|premium|custom), package_starts_at,
     * package_ends_at, template (classic|midnight|ember|null), onboarded, restaurant
     * (false for a user mid-onboarding), has_password, name, description,
     * slug, main_locale, second_locale, is_active, switched_off, phone (null for none), google_maps_url,
     * opening_hours, categories [{name, description, icon, dishes: [{name, price,
     * ingredients, is_available, image (bool: a photo), variants: [{name, options: [{name, price}]}],
     * addons: [{name, price}], addon_groups: [{name, min, max, addons: [{name, price}]}],
     * builds: [{name, shape, parts, category (a
     * scenario category's English name, its dishes the choices; dishes:
     * [English dish name] for only those of them), choices:
     * [{name, price, part_prices}] (else its own list), part_prices:
     * {English dish name: {count: price}}}]}]}], social_links [{platform, url}], order_mode
     * (whatsapp|menu), order_types ([delivery, pickup]), orders (count),
     * order_channel (menu, the default: placed in the menu with a phone and
     * an address | whatsapp), order_choices (bool: their line carries a
     * size and an add-on),
     * visits (count), qr_scans (count), settings {key: value} for the
     * design, qr_settings {…}, logo (bool), cover (bool: a wide
     * picture), writing_help_days [{days_ago,
     * uses, input_tokens, output_tokens, cost, credits}] (logged AI use, its
     * credits counting against the month's), overrides
     * [{feature, value}] (an admin's set grant: a limit, null unlimited, or a
     * flag 0 off / 1 on), price_cents (the restaurant's own price), offers
     * [{title, description, placements, action, action_url, show_again,
     * delay_seconds, ad_seconds, discount_type (none, percent, amount,
     * second), discount_value, dishes
     * (English names of the scenario's dishes), starts_at, ends_at (UTC),
     * repeat, weekdays, month_days, time_windows, shows_outside_hours,
     * is_active, image (bool: a
     * wide picture), deal {name, price, category (a scenario category's
     * English name), ingredients?} (the offer's own dish, its one dish)}];
     * each offer comes back as {id, deal_id}.
     */
    public function scenario(Request $request): JsonResponse
    {
        $input = $request->all();
        $email = 'owner-'.Str::lower(Str::random(10)).'@e2e.test';

        $user = User::query()->create([
            'name' => 'E2E Owner',
            'email' => $email,
            'password' => ($input['has_password'] ?? true) ? E2eSeeder::PASSWORD : null,
            'onboarding_step' => ($input['onboarded'] ?? true) ? User::ONBOARDING_STEPS : 0,
            'onboarding_completed_at' => ($input['onboarded'] ?? true) ? now() : null,
        ]);

        $result = ['user' => ['email' => $email, 'password' => E2eSeeder::PASSWORD]];

        if (($input['restaurant'] ?? true) === false) {
            return response()->json($result, 201);
        }

        $template = array_key_exists('template', $input)
            ? ($input['template'] === null ? null : Template::query()->where('slug', $input['template'])->firstOrFail())
            : Template::query()->where('slug', 'classic')->firstOrFail();

        $restaurant = Restaurant::query()->create([
            'user_id' => $user->id,
            'name' => $input['name'] ?? ['en' => 'E2E Kitchen'],
            'description' => $input['description'] ?? ['en' => 'Grills and mezze, made to share.'],
            'slug' => $input['slug'] ?? 'e2e-'.Str::lower(Str::random(8)),
            'template_id' => $template?->id,
            'is_active' => $input['is_active'] ?? true,
            'country_code' => 'LB',
            // null: no phone at all (no WhatsApp to reach).
            'phone' => array_key_exists('phone', $input) ? $input['phone'] : '+96170123456',
            'currency' => $input['currency'] ?? 'USD',
            'second_currency' => $input['second_currency'] ?? null,
            'exchange_rate' => $input['exchange_rate'] ?? null,
            'timezone' => 'Asia/Beirut',
            'google_maps_url' => $input['google_maps_url'] ?? null,
            'opening_hours' => $input['opening_hours'] ?? null,
            'main_locale' => $input['main_locale'] ?? 'en',
            'default_locale' => $input['main_locale'] ?? 'en',
            'second_locale' => $input['second_locale'] ?? null,
            'qr_settings' => $input['qr_settings'] ?? null,
            'order_mode' => $input['order_mode'] ?? 'whatsapp',
            'order_types' => $input['order_types'] ?? null,
            'template_settings' => $template && isset($input['settings']) ? [$template->id => $input['settings']] : null,
        ]);

        $this->assignPackage($restaurant, $input);
        // After the package: a package arriving switches on what it brings,
        // and this is the owner's choice as it stands.
        $restaurant->update(['switched_off' => $input['switched_off'] ?? []]);

        if ($input['logo'] ?? false) {
            $restaurant->addMediaFromString($this->png())->usingFileName('logo.png')->toMediaCollection('logo');
        }

        if ($input['cover'] ?? false) {
            $restaurant->addMediaFromString($this->photo())->usingFileName('cover.jpg')->withCustomProperties(['width' => 1200, 'height' => 800])->toMediaCollection('cover_image');
        }

        $categories = [];
        // Built from a category that may come later: made once every one is.
        $builds = [];
        foreach (array_values($input['categories'] ?? []) as $position => $row) {
            $category = Category::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
                'icon' => $row['icon'] ?? null,
                'display_order' => $position,
            ]);

            $dishes = [];
            foreach (array_values($row['dishes'] ?? []) as $order => $dish) {
                $created = Dish::query()->create([
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $category->id,
                    'name' => $dish['name'],
                    'ingredients' => $dish['ingredients'] ?? null,
                    'price' => array_key_exists('price', $dish) ? $dish['price'] : 10,
                    'currency' => $dish['currency'] ?? null,
                    'is_available' => $dish['is_available'] ?? true,
                    'display_order' => $order,
                ]);
                if ($dish['image'] ?? false) {
                    $created->addMediaFromString($this->photo())->usingFileName('dish.jpg')->toMediaCollection('image');
                }
                $this->dishOptions($created, $dish);
                $builds[] = [$created, $dish['builds'] ?? []];
                $dishes[] = $created->only(['id']);
            }

            $categories[] = ['id' => $category->id, 'dishes' => $dishes];
        }

        foreach ($builds as [$dish, $rows]) {
            $this->dishBuilds($restaurant, $dish, $rows);
        }

        foreach ($input['social_links'] ?? [] as $link) {
            $restaurant->socialLinks()->create($link);
        }

        foreach ($input['overrides'] ?? [] as $override) {
            $restaurant->featureGrants()->create([
                'feature' => $override['feature'],
                'mode' => GrantMode::Set,
                'value' => $override['value'],
            ]);
        }

        $offers = [];
        foreach (array_values($input['offers'] ?? []) as $order => $row) {
            $offer = Offer::query()->create([
                'restaurant_id' => $restaurant->id,
                'display_order' => $order,
                ...collect($row)->except(['dishes', 'image', 'deal'])->all(),
            ]);
            if ($row['image'] ?? false) {
                // Its size kept, as a real upload's is (MediaService::store()).
                $offer->addMediaFromString($this->photo())->usingFileName('offer.jpg')->withCustomProperties(['width' => 1200, 'height' => 800])->toMediaCollection('image');
            }
            $named = $restaurant->dishes()->get()->filter(fn (Dish $dish): bool => in_array($dish->getTranslation('name', 'en', false), $row['dishes'] ?? [], true));
            $offer->dishes()->sync($named->pluck('id')->all());
            $deal = isset($row['deal']) ? $this->dealDish($restaurant, $offer, $row['deal']) : null;
            $offers[] = ['id' => $offer->id, 'deal_id' => $deal?->id];
        }

        // Logged AI use with its cost, as WritingHelpLog writes it, for the
        // admin's spending figures: [{days_ago, uses, input_tokens, output_tokens, cost}].
        foreach ($input['writing_help_days'] ?? [] as $row) {
            WritingHelpDay::query()->create([
                'restaurant_id' => $restaurant->id,
                'day' => now($restaurant->localTimezone())->subDays((int) ($row['days_ago'] ?? 0)),
                'uses' => (int) ($row['uses'] ?? 0),
                'input_tokens' => (int) ($row['input_tokens'] ?? 0),
                'output_tokens' => (int) ($row['output_tokens'] ?? 0),
                'cost' => (string) ($row['cost'] ?? '0'),
                'credits' => (int) ($row['credits'] ?? 0),
            ]);
        }

        for ($i = 0; $i < (int) ($input['orders'] ?? 0); $i++) {
            $factory = ($input['order_channel'] ?? 'menu') === 'menu' ? Order::factory()->inMenu() : Order::factory();
            $order = $factory->create([
                'restaurant_id' => $restaurant->id,
                'status' => OrderStatus::Placed,
                'currency' => $restaurant->currency,
                'total' => '12.50',
                // Seconds apart, newest first: still today just after midnight,
                // when the analytics open on Today.
                'placed_at' => now()->subSeconds(10 * ($i + 1)),
            ]);
            $order->items()->create([
                'name' => 'Kafta',
                'options' => ($input['order_choices'] ?? false)
                    ? ['variants' => [['name' => 'Size', 'choice' => 'Large', 'price' => '2.00']], 'addons' => [['name' => 'Extra garlic', 'price' => '0.50']]]
                    : null,
                'unit_price' => '12.50',
                'quantity' => 1,
                'line_total' => '12.50',
            ]);
        }

        $visits = (int) ($input['visits'] ?? 0);
        $scans = (int) ($input['qr_scans'] ?? 0);
        for ($i = 0; $i < $visits; $i++) {
            $restaurant->menuSessions()->create([
                'session_id' => 'e2e-'.$i,
                'viewed_at' => now()->subSeconds($i),
                'via_qr' => $i < $scans,
                'locale' => 'en',
            ]);
        }

        return response()->json([
            ...$result,
            'restaurant' => [
                'id' => $restaurant->id,
                'slug' => $restaurant->slug,
                'public_url' => url($restaurant->slug),
            ],
            'categories' => $categories,
            'offers' => $offers,
        ], 201);
    }

    /** Sign a user in on this browser session, as the login form would. */
    public function login(Request $request): Response
    {
        $user = User::query()->where('email', $request->string('email'))->firstOrFail();

        // A browser that was someone else a moment ago starts clean, as after
        // a real logout: the old session's password hash would sign the new
        // user straight out.
        Auth::guard('web')->logout();
        $request->session()->invalidate();

        Auth::login($user);
        $request->session()->regenerate();

        return response()->noContent();
    }

    /** Move a restaurant to a package, as an admin would. */
    public function package(Request $request): Response
    {
        $restaurant = Restaurant::query()->findOrFail($request->integer('restaurant_id'));
        $this->assignPackage($restaurant, $request->all());

        return response()->noContent();
    }

    /** A valid password-reset token, standing in for the email. */
    public function passwordResetToken(Request $request): JsonResponse
    {
        $user = User::query()->where('email', $request->string('email'))->firstOrFail();

        return response()->json(['token' => Password::createToken($user)]);
    }

    /** @param  array<string, mixed>  $input */
    private function assignPackage(Restaurant $restaurant, array $input): void
    {
        app(PackageAssigner::class)->assign(
            $restaurant,
            Package::findBySlug($input['package'] ?? 'free') ?? Package::default(),
            isset($input['package_starts_at']) ? CarbonImmutable::parse($input['package_starts_at']) : now()->subDay(),
            isset($input['package_ends_at']) ? CarbonImmutable::parse($input['package_ends_at']) : null,
        );

        // After the package: a new package clears a price agreed for the old one.
        if (isset($input['price_cents'])) {
            $restaurant->update(['price_cents' => (int) $input['price_cents']]);
        }
    }

    /**
     * The offer's own dish, as the offer API leaves it: in the named
     * category, last there, and the offer's one dish.
     *
     * @param  array{name: array<string, string>, price: float|int|string, category: string, ingredients?: array<string, string>|null}  $row
     */
    private function dealDish(Restaurant $restaurant, Offer $offer, array $row): Dish
    {
        $category = $restaurant->categories()->get()->first(fn (Category $category): bool => $category->getTranslation('name', 'en', false) === $row['category']);

        $deal = new Dish([
            'name' => $row['name'],
            'ingredients' => $row['ingredients'] ?? null,
            'price' => $row['price'],
            'category_id' => $category?->id,
            'is_available' => true,
            'display_order' => (int) $restaurant->dishes()->where('category_id', $category?->id)->max('display_order') + 1,
        ]);
        $deal->restaurant()->associate($restaurant);
        $deal->offer()->associate($offer);
        $deal->save();
        $offer->dishes()->sync([$deal->id]);

        return $deal;
    }

    /** A wide picture with some depth to it, like a food photo: an offer's, a cover, a dish's. */
    private function photo(): string
    {
        $image = imagecreatetruecolor(1200, 800);
        for ($y = 0; $y < 800; $y++) {
            $shade = (int) (60 + $y / 800 * 120);
            imageline($image, 0, $y, 1199, $y, (int) imagecolorallocate($image, $shade + 60, (int) ($shade * 0.7), 40));
        }
        imagefilledellipse($image, 780, 420, 520, 420, (int) imagecolorallocate($image, 236, 196, 120));
        imagefilledellipse($image, 700, 380, 180, 140, (int) imagecolorallocate($image, 150, 60, 30));
        ob_start();
        imagejpeg($image, null, 85);

        return (string) ob_get_clean();
    }

    /** A small square logo, so no fixture file is needed. */
    private function png(): string
    {
        $image = imagecreatetruecolor(256, 256);
        imagefill($image, 0, 0, (int) imagecolorallocate($image, 248, 211, 141));
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }

    /**
     * A scenario dish's builds: from a category named in English (only the
     * dishes named, when some are), with part prices for its dishes by
     * English name, or with a list of its own.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function dishBuilds(Restaurant $restaurant, Dish $dish, array $rows): void
    {
        foreach (array_values($rows) as $position => $row) {
            $category = isset($row['category'])
                ? $restaurant->categories()->get()->first(fn (Category $category): bool => $category->getTranslation('name', 'en') === $row['category'])
                : null;
            $only = isset($row['dishes'])
                ? $restaurant->dishes()->get()->filter(fn (Dish $choice): bool => in_array($choice->getTranslation('name', 'en'), (array) $row['dishes'], true))->pluck('id')->values()->all()
                : null;
            $build = $dish->builds()->create([
                'name' => $row['name'],
                'shape' => $row['shape'] ?? 'split',
                'parts' => $row['parts'] ?? [1, 2],
                'category_id' => $category?->id,
                'dish_ids' => $only,
                'display_order' => $position,
            ]);

            foreach (array_values($row['choices'] ?? []) as $place => $choice) {
                $build->choices()->create(['name' => $choice['name'], 'price' => $choice['price'] ?? 0, 'part_prices' => $choice['part_prices'] ?? null, 'display_order' => $place]);
            }

            foreach ((array) ($row['part_prices'] ?? []) as $name => $prices) {
                $priced = $restaurant->dishes()->get()->first(fn (Dish $choice): bool => $choice->getTranslation('name', 'en') === $name);
                $build->choices()->create(['dish_id' => $priced?->id, 'part_prices' => $prices]);
            }
        }
    }

    /**
     * A scenario dish's variants and add-ons, in the order given.
     *
     * @param  array<string, mixed>  $input
     */
    private function dishOptions(Dish $dish, array $input): void
    {
        foreach (array_values($input['variants'] ?? []) as $position => $row) {
            $variant = $dish->variants()->create(['name' => $row['name'], 'display_order' => $position]);

            foreach (array_values($row['options'] ?? []) as $place => $option) {
                $variant->options()->create(['name' => $option['name'], 'price' => $option['price'] ?? 0, 'display_order' => $place]);
            }
        }

        foreach (array_values($input['addons'] ?? []) as $position => $addon) {
            $dish->addons()->create(['name' => $addon['name'], 'price' => $addon['price'] ?? 0, 'display_order' => $position]);
        }

        // Add-ons in a group, as a POS makes them: after the dish's own.
        foreach (array_values($input['addon_groups'] ?? []) as $position => $row) {
            $group = $dish->addonGroups()->create(['name' => $row['name'], 'min_select' => $row['min'] ?? 0, 'max_select' => $row['max'] ?? null, 'display_order' => $position]);

            foreach (array_values($row['addons'] ?? []) as $place => $addon) {
                $group->addons()->create(['dish_id' => $dish->id, 'name' => $addon['name'], 'price' => $addon['price'] ?? 0, 'display_order' => 100 + $place]);
            }
        }
    }
}
