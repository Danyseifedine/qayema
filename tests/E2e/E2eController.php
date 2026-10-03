<?php

namespace Tests\E2e;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;
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
     * package_ends_at, template (classic|midnight|null), onboarded, restaurant
     * (false for a user mid-onboarding), has_password, name, description,
     * slug, second_locale, is_active, switched_off, phone, google_maps_url,
     * opening_hours, categories [{name, description, dishes: [{name, price,
     * ingredients, is_available, variants: [{name, options: [{name, price}]}],
     * addons: [{name, price}]}]}], social_links [{platform, url}], order_mode
     * (whatsapp|menu), order_types ([delivery, pickup]), orders (count),
     * order_channel (menu, the default: placed in the menu with a phone and
     * an address | whatsapp), order_choices (bool: their line carries a
     * size and an add-on),
     * visits (count), qr_scans (count), settings {key: value} for the
     * design, qr_settings {…}, logo (bool).
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
            'phone' => $input['phone'] ?? '+96170123456',
            'currency' => 'USD',
            'timezone' => 'Asia/Beirut',
            'google_maps_url' => $input['google_maps_url'] ?? null,
            'opening_hours' => $input['opening_hours'] ?? null,
            'default_locale' => 'en',
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

        $categories = [];
        foreach (array_values($input['categories'] ?? []) as $position => $row) {
            $category = Category::query()->create([
                'restaurant_id' => $restaurant->id,
                'name' => $row['name'],
                'description' => $row['description'] ?? null,
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
                    'is_available' => $dish['is_available'] ?? true,
                    'display_order' => $order,
                ]);
                $this->dishOptions($created, $dish);
                $dishes[] = $created->only(['id']);
            }

            $categories[] = ['id' => $category->id, 'dishes' => $dishes];
        }

        foreach ($input['social_links'] ?? [] as $link) {
            $restaurant->socialLinks()->create($link);
        }

        for ($i = 0; $i < (int) ($input['orders'] ?? 0); $i++) {
            $factory = ($input['order_channel'] ?? 'menu') === 'menu' ? Order::factory()->inMenu() : Order::factory();
            $order = $factory->create([
                'restaurant_id' => $restaurant->id,
                'status' => OrderStatus::Placed,
                'currency' => $restaurant->currency,
                'total' => '12.50',
                'placed_at' => now()->subMinutes(10 * ($i + 1)),
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
                'viewed_at' => now()->subHours($i),
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
    }
}
