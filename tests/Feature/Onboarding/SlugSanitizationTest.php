<?php

namespace Tests\Feature\Onboarding;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SlugSanitizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<int, string>
     */
    public static function hostileSlugs(): array
    {
        return [
            ['UPPER CASE'],
            ['MY SLUG With Spaces & Special!'],
            ['  Leading and Trailing  '],
            ['&&&!!!'],
        ];
    }

    #[DataProvider('hostileSlugs')]
    public function test_onboarding_step_one_normalizes_any_slug_instead_of_crashing(string $slug): void
    {
        $user = User::factory()->create([
            'onboarding_step' => 0,
            'onboarding_completed_at' => null,
        ]);

        $response = $this->actingAs($user)->postJson(route('onboarding.advance'), [
            '_step' => 1,
            'name' => 'Test Restaurant',
            'slug' => $slug,
            'preferred_language' => 'en',
        ]);

        // Must never 500 — either it advances (200) or it is a clean 422, never a crash.
        $this->assertContains($response->status(), [200, 422]);

        $restaurant = Restaurant::where('user_id', $user->id)->first();

        if ($restaurant) {
            $this->assertMatchesRegularExpression(
                '/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                $restaurant->slug,
                "Stored slug must be a valid, space-free slug; got [{$restaurant->slug}]."
            );
        }
    }
}
