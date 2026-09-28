<?php

namespace Tests\Feature\Console;

use App\Models\Template;
use Illuminate\Contracts\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * make:menu-template when things are not as expected: a slug that slugs to
 * nothing, a design that already exists (view or row), and a checkout that
 * has lost the classic design it copies from.
 */
class MakeMenuTemplateEdgeTest extends TestCase
{
    use RefreshDatabase;

    /** @var string[] */
    private array $created = [];

    private ?string $originalBasePath = null;

    private ?string $sandbox = null;

    protected function tearDown(): void
    {
        foreach ($this->created as $path) {
            if (file_exists($path)) {
                unlink($path);
            }
        }

        if ($this->originalBasePath !== null) {
            $this->app->setBasePath($this->originalBasePath);
        }

        if ($this->sandbox !== null) {
            File::deleteDirectory($this->sandbox);
        }

        parent::tearDown();
    }

    /**
     * A slug no other test uses, so parallel runs never share files. Its view
     * and stylesheet are removed after the test.
     */
    private function uniqueSlug(): string
    {
        $slug = 'edge-'.Str::lower(Str::random(8));

        $this->created[] = resource_path("views/menu/templates/{$slug}.blade.php");
        $this->created[] = public_path("css/menu-{$slug}.css");

        return $slug;
    }

    /**
     * Point the app at an empty temporary project, so the command can be run
     * against a missing classic view without touching the real one.
     */
    private function useEmptyProject(): string
    {
        // Load the commands while the real app path is still in place.
        $this->app->make(ConsoleKernel::class)->all();

        $this->sandbox = sys_get_temp_dir().'/qayema-make-template-'.Str::random(8);
        File::ensureDirectoryExists($this->sandbox.'/resources/views/menu/templates');
        File::ensureDirectoryExists($this->sandbox.'/public/css');

        $this->originalBasePath = base_path();
        $this->app->setBasePath($this->sandbox);

        return $this->sandbox;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unusableSlugs(): array
    {
        return [
            'only symbols' => ['!!!'],
            'only spaces' => ['   '],
            'only dashes' => ['---'],
        ];
    }

    #[DataProvider('unusableSlugs')]
    public function test_a_slug_that_slugs_to_nothing_is_refused(string $slug): void
    {
        $before = Template::count();

        $this->artisan('make:menu-template', ['slug' => $slug])
            ->expectsOutputToContain('A valid slug is required.')
            ->assertFailed();

        $this->assertSame($before, Template::count());
    }

    public function test_the_slug_is_normalised_before_anything_is_written(): void
    {
        $slug = $this->uniqueSlug();

        $this->artisan('make:menu-template', ['slug' => Str::upper(str_replace('-', ' ', $slug))])
            ->assertSuccessful();

        $this->assertFileExists(resource_path("views/menu/templates/{$slug}.blade.php"));
        $this->assertFileExists(public_path("css/menu-{$slug}.css"));
        $this->assertDatabaseHas('templates', ['slug' => $slug]);
    }

    public function test_an_existing_view_is_left_alone_without_force(): void
    {
        $slug = $this->uniqueSlug();
        $view = resource_path("views/menu/templates/{$slug}.blade.php");
        file_put_contents($view, 'hand-made design');

        $this->artisan('make:menu-template', ['slug' => $slug])
            ->expectsOutputToContain('already exists. Pass --force to overwrite it.')
            ->assertFailed();

        $this->assertSame('hand-made design', file_get_contents($view));
        $this->assertFileDoesNotExist(public_path("css/menu-{$slug}.css"));
        $this->assertDatabaseMissing('templates', ['slug' => $slug]);
    }

    public function test_force_overwrites_the_view_and_stylesheet_and_updates_the_existing_row(): void
    {
        $slug = $this->uniqueSlug();

        $this->artisan('make:menu-template', ['slug' => $slug, '--name' => 'First', '--inactive' => true])
            ->assertSuccessful();
        $first = Template::where('slug', $slug)->sole();

        file_put_contents(resource_path("views/menu/templates/{$slug}.blade.php"), 'edited');
        file_put_contents(public_path("css/menu-{$slug}.css"), '/* edited */');

        $this->artisan('make:menu-template', ['slug' => $slug, '--name' => 'Second', '--force' => true])
            ->expectsOutputToContain('Template [Second] created.')
            ->assertSuccessful();

        $second = Template::where('slug', $slug)->sole();
        $this->assertSame($first->id, $second->id, 'The row is updated, not duplicated.');
        $this->assertSame('Second', $second->getTranslation('name', 'en'));
        $this->assertTrue($second->is_active);

        $view = (string) file_get_contents(resource_path("views/menu/templates/{$slug}.blade.php"));
        $this->assertStringContainsString("Template: {$slug}", $view);
        $this->assertStringContainsString("css/menu-{$slug}.css", $view);
        $this->assertStringNotContainsString('css/menu-classic.css', $view);
        $this->assertFileEquals(public_path('css/menu-classic.css'), public_path("css/menu-{$slug}.css"));
    }

    public function test_a_row_without_a_view_gets_its_view_and_keeps_its_id(): void
    {
        $slug = $this->uniqueSlug();
        $row = Template::create([
            'slug' => $slug,
            'name' => ['en' => 'Orphan'],
            'is_active' => false,
            'settings_schema' => [],
        ]);

        $this->artisan('make:menu-template', ['slug' => $slug])->assertSuccessful();

        $row->refresh();
        $this->assertSame(1, Template::where('slug', $slug)->count());
        $this->assertSame(Str::headline($slug), $row->getTranslation('name', 'en'));
        $this->assertTrue($row->is_active);
        $this->assertSame(Template::CLASSIC_SCHEMA, $row->settings_schema);
        $this->assertFileExists(resource_path("views/menu/templates/{$slug}.blade.php"));
    }

    public function test_it_reports_where_everything_went(): void
    {
        $slug = $this->uniqueSlug();

        $this->artisan('make:menu-template', ['slug' => $slug, '--name' => 'Night Market'])
            ->expectsOutputToContain('Template [Night Market] created.')
            ->expectsOutputToContain("resources/views/menu/templates/{$slug}.blade.php")
            ->expectsOutputToContain("public/css/menu-{$slug}.css")
            ->assertSuccessful();
    }

    public function test_a_missing_classic_view_stops_it_before_anything_is_written(): void
    {
        $sandbox = $this->useEmptyProject();

        $this->artisan('make:menu-template', ['slug' => 'midnight'])
            ->expectsOutputToContain('The classic template is missing, so there is nothing to copy from.')
            ->assertFailed();

        $this->assertFileDoesNotExist($sandbox.'/resources/views/menu/templates/midnight.blade.php');
        $this->assertFileDoesNotExist($sandbox.'/public/css/menu-midnight.css');
        $this->assertDatabaseMissing('templates', ['slug' => 'midnight']);
    }
}
