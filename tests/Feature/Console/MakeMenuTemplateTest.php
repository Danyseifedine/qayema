<?php

namespace Tests\Feature\Console;

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
        $this->created[] = public_path("css/menu-{$slug}.css");

        return $this->created[] = resource_path("views/menu/templates/{$slug}.blade.php");
    }

    public function test_it_creates_the_row_and_the_view(): void
    {
        $path = $this->viewPath('midnight');

        $this->artisan('make:menu-template', ['slug' => 'midnight'])
            ->assertSuccessful();

        $this->assertFileExists($path);
        // Its own stylesheet, linked from the copied view, so restyling it
        // never touches classic.
        $this->assertFileExists(public_path('css/menu-midnight.css'));
        $this->assertStringContainsString('css/menu-midnight.css', (string) file_get_contents($path));
        $this->assertDatabaseHas('templates', ['slug' => 'midnight', 'is_active' => true]);
    }

    public function test_the_scaffolded_template_renders_a_real_menu(): void
    {
        $this->viewPath('midnight');

        $this->artisan('make:menu-template', ['slug' => 'midnight'])
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

    public function test_it_names_the_template_from_the_slug(): void
    {
        $this->viewPath('plain');

        $this->artisan('make:menu-template', ['slug' => 'plain'])->assertSuccessful();

        $this->assertDatabaseHas('templates', ['slug' => 'plain', 'name' => '{"en":"Plain"}']);
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
