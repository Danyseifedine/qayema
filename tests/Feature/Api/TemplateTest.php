<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Appearance);
    }

    /**
     * @return array{0: User, 1: Restaurant}
     */
    private function owner(): array
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['user_id' => $user->id, 'template_id' => null]);

        return [$user, $restaurant];
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson(route('api.templates.index'))->assertUnauthorized();
    }

    public function test_index_lists_active_templates_and_the_current_selection(): void
    {
        [$user] = $this->owner();
        $active = Template::factory()->create(['is_active' => true, 'slug' => 'default', 'name' => ['en' => 'Default']]);
        Template::factory()->create(['is_active' => false, 'slug' => 'hidden']);

        $response = $this->actingAs($user)->getJson(route('api.templates.index'))->assertOk();

        $response->assertJsonStructure([
            'data' => [['id', 'slug', 'name' => ['en', 'ar'], 'description' => ['en', 'ar']]],
            'meta' => ['current'],
        ]);

        // Only active templates are offered, and nothing is selected yet.
        $ids = array_column($response->json('data'), 'id');
        $this->assertSame([$active->id], $ids);
        $response->assertJsonPath('meta.current', null);
    }

    public function test_select_applies_a_template_to_the_restaurant(): void
    {
        [$user, $restaurant] = $this->owner();
        $template = Template::factory()->create(['is_active' => true, 'slug' => 'chosen']);

        $this->actingAs($user)
            ->postJson(route('api.templates.select'), ['template_id' => $template->id])
            ->assertOk()
            ->assertJsonPath('meta.current', $template->id);

        $this->assertSame($template->id, $restaurant->fresh()->template_id);
    }

    public function test_select_rejects_an_inactive_template(): void
    {
        [$user] = $this->owner();
        $template = Template::factory()->create(['is_active' => false, 'slug' => 'inactive']);

        $this->actingAs($user)
            ->postJson(route('api.templates.select'), ['template_id' => $template->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_id');
    }

    public function test_select_rejects_an_unknown_template(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.templates.select'), ['template_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_id');
    }

    public function test_select_requires_a_template_id(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.templates.select'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_id');
    }

    public function test_selecting_a_template_keeps_the_colours_chosen_for_others(): void
    {
        [$user, $restaurant] = $this->owner();
        $styled = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#C8A85A'],
        ])->create(['slug' => 'styled']);
        $restaurant->update(['template_settings' => [$styled->id => ['primary_color' => '#112233']]]);

        $this->actingAs($user)
            ->postJson(route('api.templates.select'), ['template_id' => $styled->id])
            ->assertOk();

        // Nothing is reset on the way in: the colours chosen before return.
        $this->assertSame([$styled->id => ['primary_color' => '#112233']], $restaurant->fresh()->template_settings);
        $this->assertSame('#112233', $restaurant->fresh()->designSettings()['primary_color']);
    }

    public function test_every_active_template_is_selectable(): void
    {
        [$user, $restaurant] = $this->owner();
        $first = Template::factory()->create(['slug' => 'first-design', 'sort_order' => 0]);
        $second = Template::factory()->create(['slug' => 'second-design', 'sort_order' => 1]);

        // Designs carry no price and nothing to unlock, so an owner can move
        // between any two of them.
        $this->actingAs($user)
            ->postJson(route('api.templates.select'), ['template_id' => $first->id])
            ->assertOk()
            ->assertJsonPath('meta.current', $first->id);

        $this->actingAs($user)
            ->postJson(route('api.templates.select'), ['template_id' => $second->id])
            ->assertOk()
            ->assertJsonPath('meta.current', $second->id);

        $this->assertSame($second->id, $restaurant->fresh()->template_id);
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $user = User::factory()->create();
        $template = Template::factory()->create(['is_active' => true, 'slug' => 'forbidden-tpl']);

        $this->actingAs($user)->getJson(route('api.templates.index'))->assertForbidden();
        $this->actingAs($user)
            ->postJson(route('api.templates.select'), ['template_id' => $template->id])
            ->assertForbidden();
    }
}
