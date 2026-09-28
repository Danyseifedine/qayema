<?php

namespace App\Http\Controllers\E2e;

use App\Enums\Feature;
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
use Database\Seeders\E2eSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Test-only endpoints for the Playwright suite (routes/e2e.php, registered in
 * the `e2e` environment only). Each spec builds the owner it needs here, so
 * specs share nothing and can run side by side.
 */
class E2eController extends Controller
{
    /**
     * Build an owner and everything they need.
     *
     * Body (all optional): package (free|pro|premium|custom), package_starts_at,
     * package_ends_at, template (classic|midnight|null), onboarded, restaurant
     * (false for a user mid-onboarding), has_password, name, second_locale,
     * default_locale, is_active, switched_off, currency, phone,
     * google_maps_url, opening_hours, categories [{name, description,
     * dishes: [{name, price, ingredients, is_available}]}], social_links
     * [{platform, url}], orders (count), visits (count), qr_scans (count),
     * grants [{feature, value, ends_at}], settings {key: value} for the
     * design, menu_fonts {script: family}, qr_settings {…}, logo (bool).
     */
    public function scenario(Request $request): JsonResponse
    {
        $input = $request->all();
        $email = 'owner-'.Str::lower(Str::random(10)).'@e2e.test';

        $user = User::query()->create([
            'name' => $input['owner_name'] ?? 'E2E Owner',
            'email' => $email,
            'password' => ($input['has_password'] ?? true) ? E2eSeeder::PASSWORD : null,
            'onboarding_step' => ($input['onboarded'] ?? true) ? User::ONBOARDING_STEPS : 0,
            'onboarding_completed_at' => ($input['onboarded'] ?? true) ? now() : null,
        ]);

        $result = ['user' => ['id' => $user->id, 'email' => $email, 'password' => E2eSeeder::PASSWORD]];

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
            'currency' => $input['currency'] ?? 'USD',
            'timezone' => $input['timezone'] ?? 'Asia/Beirut',
            'google_maps_url' => $input['google_maps_url'] ?? null,
            'opening_hours' => $input['opening_hours'] ?? null,
            'default_locale' => $input['default_locale'] ?? 'en',
            'second_locale' => $input['second_locale'] ?? null,
            'switched_off' => $input['switched_off'] ?? [],
            'menu_fonts' => $input['menu_fonts'] ?? null,
            'qr_settings' => $input['qr_settings'] ?? null,
            'template_settings' => $template && isset($input['settings']) ? [$template->id => $input['settings']] : null,
        ]);

        $this->assignPackage($restaurant, $input);

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
                $dishes[] = Dish::query()->create([
                    'restaurant_id' => $restaurant->id,
                    'category_id' => $category->id,
                    'name' => $dish['name'],
                    'ingredients' => $dish['ingredients'] ?? null,
                    'price' => $dish['price'] ?? 10,
                    'is_available' => $dish['is_available'] ?? true,
                    'display_order' => $order,
                ])->only(['id']);
            }

            $categories[] = ['id' => $category->id, 'dishes' => $dishes];
        }

        foreach ($input['social_links'] ?? [] as $link) {
            $restaurant->socialLinks()->create($link);
        }

        for ($i = 0; $i < (int) ($input['orders'] ?? 0); $i++) {
            $order = Order::factory()->create([
                'restaurant_id' => $restaurant->id,
                'status' => OrderStatus::Placed,
                'currency' => $restaurant->currency,
                'total' => '12.50',
                'placed_at' => now()->subMinutes(10 * ($i + 1)),
            ]);
            $order->items()->create(['name' => 'Kafta', 'unit_price' => '12.50', 'quantity' => 1, 'line_total' => '12.50']);
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

        foreach ($input['grants'] ?? [] as $grant) {
            $restaurant->featureGrants()->create([
                'feature' => Feature::from($grant['feature']),
                'value' => $grant['value'] ?? 1,
                'source' => 'admin',
                'ends_at' => $grant['ends_at'] ?? null,
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
}
