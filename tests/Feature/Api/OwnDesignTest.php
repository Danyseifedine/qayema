<?php

namespace Tests\Feature\Api;

use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A design made for one restaurant (`templates.restaurant_id`): only that
 * one sees it and may pick it, whatever its package; for everyone else it
 * is not there at all.
 */
class OwnDesignTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function ownDesign(Restaurant $for, array $state = []): Template
    {
        return Template::factory()->create(['slug' => 'olive-gold', 'restaurant_id' => $for->id, ...$state]);
    }

    public function test_only_the_restaurant_it_was_made_for_sees_it(): void
    {
        $olive = $this->owner();
        $cedar = $this->owner();
        $design = $this->ownDesign($olive);

        $this->actingAs($olive->user)->getJson(route('api.templates.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => $design->id, 'made_for_you' => true, 'locked' => false]);

        $listed = array_column($this->actingAs($cedar->user)->getJson(route('api.templates.index'))->json('data'), 'id');
        $this->assertNotContains($design->id, $listed);
    }

    public function test_it_is_picked_whatever_the_package_and_nobody_else_can_take_it(): void
    {
        // On Free, and marked premium too: it is theirs all the same.
        $olive = $this->owner();
        $design = $this->ownDesign($olive, ['is_premium' => true]);

        $this->actingAs($olive->user)->postJson(route('api.templates.select'), ['template_id' => $design->id])->assertOk();
        $this->assertSame($design->id, $olive->fresh()->menuTemplate()?->id);

        $cedar = $this->owner();
        $this->actingAs($cedar->user)->postJson(route('api.templates.select'), ['template_id' => $design->id])->assertNotFound();
    }

    public function test_it_is_never_anyone_elses_fallback(): void
    {
        $olive = $this->owner();
        $design = $this->ownDesign($olive, ['sort_order' => -1]);
        $classic = Template::query()->where('slug', 'classic')->first() ?? Template::factory()->create(['slug' => 'classic']);

        $this->assertNotSame($design->id, Template::fallback()?->id);
        $this->assertSame($classic->id, Template::fallback()?->id);
    }

    public function test_only_its_owner_previews_it_on_their_menu(): void
    {
        $olive = $this->published();
        $cedar = $this->published();
        $design = $this->ownDesign($olive);

        $this->assertTrue($olive->mayUseTemplate($design));
        $this->assertFalse($cedar->mayUseTemplate($design));

        // Another restaurant's owner asking for it sees their own menu as it is.
        $this->actingAs($cedar->user)
            ->get(route('public.menu', $cedar->slug).'?preview='.$design->id)
            ->assertOk()
            ->assertDontSee('olive-gold', false);
    }

    public function test_it_goes_when_its_restaurant_does(): void
    {
        $olive = $this->owner();
        $design = $this->ownDesign($olive);

        $olive->delete();

        $this->assertModelMissing($design);
    }
}
