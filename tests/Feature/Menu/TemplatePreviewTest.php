<?php

namespace Tests\Feature\Menu;

use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `?preview={template_id}` lets the owner see any design on their real menu
 * before switching their menu over to it.
 */
class TemplatePreviewTest extends TestCase
{
    use RefreshDatabase;

    private function restaurantOn(?Template $template): Restaurant
    {
        return Restaurant::factory()->create([
            'slug' => 'preview-me',
            'name' => ['en' => 'Preview Diner'],
            'default_locale' => 'en',
            'template_id' => $template?->id,
        ]);
    }

    private function candidate(): Template
    {
        return Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#ABCDEF'],
        ])->create(['slug' => 'classic']);
    }

    public function test_the_owner_can_preview_a_template_they_do_not_own(): void
    {
        $current = Template::factory()->create(['slug' => 'classic']);
        $restaurant = $this->restaurantOn($current);
        $restaurant->update(['template_settings' => [$current->id => ['primary_color' => '#111111']]]);
        $candidate = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#ABCDEF'],
        ])->create(['slug' => 'midnight']);

        $this->actingAs($restaurant->user)
            ->get(route('public.menu', $restaurant->slug).'?preview='.$candidate->id)
            ->assertOk()
            // The candidate's defaults render: the owner's colours belong to
            // the design they saved them on.
            ->assertSee('--accent: #ABCDEF', false)
            ->assertDontSee('--accent: #111111', false);
    }

    public function test_the_owner_can_preview_before_choosing_any_template(): void
    {
        $restaurant = $this->restaurantOn(null);
        $candidate = $this->candidate();

        // Without a template the public page is a 404; the preview still works.
        $this->get(route('public.menu', $restaurant->slug))->assertNotFound();

        $this->actingAs($restaurant->user)
            ->get(route('public.menu', $restaurant->slug).'?preview='.$candidate->id)
            ->assertOk()
            ->assertSee('Preview Diner');
    }

    public function test_a_preview_is_not_counted_as_a_visit(): void
    {
        $restaurant = $this->restaurantOn($this->candidate());

        $this->actingAs($restaurant->user)
            ->get(route('public.menu', $restaurant->slug).'?preview='.$restaurant->template_id)
            ->assertOk();

        $this->assertDatabaseCount('menu_sessions', 0);
    }

    public function test_a_guest_asking_for_a_preview_just_gets_the_normal_menu(): void
    {
        $current = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#111111'],
        ])->create(['slug' => 'classic']);
        $restaurant = $this->restaurantOn($current);
        $candidate = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#ABCDEF'],
        ])->create(['slug' => 'midnight']);

        $this->get(route('public.menu', $restaurant->slug).'?preview='.$candidate->id)
            ->assertOk()
            ->assertSee('--accent: #111111', false)
            ->assertDontSee('#ABCDEF');

        // Counted as an ordinary visit.
        $this->assertDatabaseCount('menu_sessions', 1);
    }

    public function test_another_owner_cannot_preview_on_someone_elses_menu(): void
    {
        $restaurant = $this->restaurantOn(null);
        $candidate = $this->candidate();
        $other = Restaurant::factory()->create();

        $this->actingAs($other->user)
            ->get(route('public.menu', $restaurant->slug).'?preview='.$candidate->id)
            ->assertNotFound();
    }

    public function test_a_user_without_a_restaurant_cannot_preview(): void
    {
        $restaurant = $this->restaurantOn(null);
        $candidate = $this->candidate();

        $this->actingAs(User::factory()->create())
            ->get(route('public.menu', $restaurant->slug).'?preview='.$candidate->id)
            ->assertNotFound();
    }

    public function test_an_inactive_or_unknown_template_cannot_be_previewed(): void
    {
        $restaurant = $this->restaurantOn(null);
        $hidden = Template::factory()->inactive()->create(['slug' => 'hidden']);

        $this->actingAs($restaurant->user)
            ->get(route('public.menu', $restaurant->slug).'?preview='.$hidden->id)
            ->assertNotFound();

        $this->actingAs($restaurant->user)
            ->get(route('public.menu', $restaurant->slug).'?preview=999999')
            ->assertNotFound();
    }

    public function test_garbage_preview_values_are_ignored(): void
    {
        $restaurant = $this->restaurantOn($this->candidate());

        foreach (['abc', '-1', '1.5', '<script>'] as $garbage) {
            $this->actingAs($restaurant->user)
                ->get(route('public.menu', $restaurant->slug).'?preview='.urlencode($garbage))
                ->assertOk();
        }
    }

    public function test_the_owner_can_preview_while_the_restaurant_is_inactive(): void
    {
        $restaurant = $this->restaurantOn($this->candidate());
        $restaurant->update(['is_active' => false]);

        $this->get(route('public.menu', $restaurant->slug))->assertNotFound();

        $this->actingAs($restaurant->user)
            ->get(route('public.menu', $restaurant->slug).'?preview='.$restaurant->template_id)
            ->assertOk();
    }
}
