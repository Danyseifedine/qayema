<?php

namespace Tests\Feature\Health;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Context;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Every log line of a request carries which page it was, whose menu and who
 * was signed in (Laravel's Context, which the log and queued jobs read), so
 * Grafana can go straight to one restaurant's errors.
 */
class LogContextTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_a_menu_page_names_its_route_and_restaurant(): void
    {
        $template = Template::factory()->create(['slug' => 'classic']);
        $this->owner(['slug' => 'olive', 'is_active' => true, 'template_id' => $template->id]);

        $this->get('/olive')->assertOk();

        $this->assertSame('public.menu', Context::get('route'));
        $this->assertSame('olive', Context::get('restaurant'));
        $this->assertFalse(Context::has('user_id'));
    }

    public function test_a_signed_in_call_names_who_made_it(): void
    {
        $owner = $this->owner()->user;

        $this->actingAs($owner)->getJson('/api/user')->assertOk();

        $this->assertSame('api.user', Context::get('route'));
        $this->assertSame($owner->id, Context::get('user_id'));
        $this->assertFalse(Context::has('restaurant'));
    }

    public function test_a_route_without_a_name_is_named_by_its_address(): void
    {
        $this->get('/up')->assertOk();

        $this->assertSame('up', Context::get('route'));
    }
}
