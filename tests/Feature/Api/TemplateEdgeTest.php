<?php

namespace Tests\Feature\Api;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class TemplateEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_a_template_deactivated_after_selection_keeps_the_owner_on_it_until_they_switch(): void
    {
        $template = Template::factory()->create();
        $owner = $this->owner(['template_id' => $template->id]);
        $template->update(['is_active' => false]);

        // The listing hides it, the current pointer still reports it, and the
        // owner can move to any active template.
        $response = $this->actingAs($owner->user)->getJson(route('api.templates.index'))->assertOk();
        $this->assertNotContains($template->id, array_column($response->json('data'), 'id'));
        $response->assertJsonPath('meta.current', $template->id);

        $other = Template::factory()->create();
        $this->actingAs($owner->user)->postJson(route('api.templates.select'), ['template_id' => $other->id])->assertOk();
    }

    public function test_the_listing_is_ordered_by_sort_order_then_id(): void
    {
        $owner = $this->owner();
        $second = Template::factory()->create(['slug' => 'b', 'sort_order' => 1]);
        $first = Template::factory()->create(['slug' => 'a', 'sort_order' => 0]);

        $slugs = array_column($this->actingAs($owner->user)->getJson(route('api.templates.index'))->json('data'), 'slug');

        $this->assertSame(['a', 'b'], $slugs);
    }
}
