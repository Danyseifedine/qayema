<?php

namespace App\Console\Commands;

use App\Models\Template;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Scaffolds a menu template: the database row (schema, ordering) and the Blade
 * view that renders it, copied from `classic` as a starting point.
 */
class MakeMenuTemplate extends Command
{
    protected $signature = 'make:menu-template
                            {slug : The template slug, e.g. midnight}
                            {--name= : Display name (defaults to the slug, title-cased)}
                            {--inactive : Create it hidden from the picker}
                            {--force : Overwrite the Blade view if it already exists}';

    protected $description = 'Create a menu template row and its Blade view';

    public function handle(): int
    {
        $slug = Str::slug($this->argument('slug'));

        if ($slug === '') {
            $this->error('A valid slug is required.');

            return self::FAILURE;
        }

        $name = $this->option('name') ?: Str::headline($slug);

        $path = resource_path("views/menu/templates/{$slug}.blade.php");

        if (file_exists($path) && ! $this->option('force')) {
            $this->error("The view [{$path}] already exists. Pass --force to overwrite it.");

            return self::FAILURE;
        }

        $stub = resource_path('views/menu/templates/classic.blade.php');

        if (! file_exists($stub)) {
            $this->error('The classic template is missing, so there is nothing to copy from.');

            return self::FAILURE;
        }

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        $contents = str_replace(
            'Template: classic (free)',
            "Template: {$slug}",
            (string) file_get_contents($stub)
        );

        file_put_contents($path, $contents);

        $template = Template::updateOrCreate(['slug' => $slug], [
            'name' => ['en' => $name],
            'is_active' => ! $this->option('inactive'),
            'settings_schema' => [
                ['key' => 'primary_color', 'type' => 'color', 'default' => '#1F6FEB'],
                ['key' => 'background_color', 'type' => 'color', 'default' => '#FFFFFF'],
                ['key' => 'text_color', 'type' => 'color', 'default' => '#111418'],
            ],
        ]);

        $this->components->info("Template [{$name}] created.");
        $this->components->twoColumnDetail('Row', "templates #{$template->id}");
        $this->components->twoColumnDetail('View', str_replace(base_path().'/', '', $path));
        $this->newLine();
        $this->line('  Edit the view to design it, and adjust its settings in the admin panel.');

        return self::SUCCESS;
    }
}
