<?php

namespace Tests\Feature\Menu;

use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MakeMenuTemplateTest extends TestCase
{
    use RefreshDatabase;

    /** @var string[] */
    private array $created = [];

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        parent::tearDown();
    }

    private function viewPath(string $slug): string
    {
        return $this->created[] = resource_path("views/menu/templates/{$slug}.blade.php");
    }

    public function test_it_creates_the_row_and_the_view(): void
    {
        $path = $this->viewPath('midnight');

        $this->artisan('make:menu-template', ['slug' => 'midnight', '--price' => 650])
            ->assertSuccessful();

        $this->assertFileExists($path);
        $this->assertDatabaseHas('templates', ['slug' => 'midnight', 'price' => 650, 'is_active' => true]);
    }

    public function test_the_scaffolded_template_renders_a_real_menu(): void
    {
        $this->viewPath('midnight');

        $this->artisan('make:menu-template', ['slug' => 'midnight', '--price' => 650])
            ->assertSuccessful();

        $template = Template::where('slug', 'midnight')->firstOrFail();
        $restaurant = Restaurant::factory()->create([
            'slug' => 'scaffolded',
            'name' => ['en' => 'Scaffolded Diner'],
            'default_locale' => 'en',
            'template_id' => $template->id,
            'template_settings' => $template->defaultSettings(),
        ]);

        // The whole point: a brand new template is live for guests immediately.
        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('Scaffolded Diner');
    }

    public function test_it_defaults_to_a_free_template(): void
    {
        $this->viewPath('plain');

        $this->artisan('make:menu-template', ['slug' => 'plain'])->assertSuccessful();

        $this->assertDatabaseHas('templates', ['slug' => 'plain', 'price' => 0]);
    }

    public function test_it_refuses_to_overwrite_an_existing_view(): void
    {
        $this->artisan('make:menu-template', ['slug' => 'classic'])->assertFailed();
    }

    public function test_it_can_create_a_hidden_template(): void
    {
        $this->viewPath('unreleased');

        $this->artisan('make:menu-template', ['slug' => 'unreleased', '--inactive' => true])
            ->assertSuccessful();

        $this->assertDatabaseHas('templates', ['slug' => 'unreleased', 'is_active' => false]);
    }
}
