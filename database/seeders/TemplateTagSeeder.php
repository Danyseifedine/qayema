<?php

namespace Database\Seeders;

use App\Models\Tag;
use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateTagSeeder extends Seeder
{
    public function run(): void
    {
        // Map template slugs to the tag slugs they should carry.
        // Skips silently if a template or tag doesn't exist yet.
        $map = [
            'default' => ['light', 'minimal', 'classic'],
            'dark' => ['dark', 'bold', 'modern'],
            'elegant' => ['light', 'elegant', 'fine-dining'],
            'minimal' => ['light', 'minimal', 'casual'],
            'bold' => ['dark', 'bold', 'street-food'],
        ];

        foreach ($map as $templateSlug => $tagSlugs) {
            $template = Template::where('slug', $templateSlug)->first();

            if (! $template) {
                continue;
            }

            $tagIds = Tag::whereIn('slug', $tagSlugs)->pluck('id');
            $template->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
