<?php

namespace Tests\Integration\Resources;

use App\Enums\Feature;
use App\Http\Resources\TemplateResource;
use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class TemplateResourceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array<string, mixed> */
    private function resolve(Template $template, ?User $user): array
    {
        $request = Request::create('/api/templates');
        $request->setUserResolver(fn (): ?User => $user);

        return (new TemplateResource($template))->resolve($request);
    }

    public function test_the_full_shape(): void
    {
        $schema = [['key' => 'primary_color', 'type' => 'color', 'default' => '#112233', 'label' => ['en' => 'Accent', 'ar' => 'اللون']]];
        $template = Template::factory()->withSettings($schema)->create([
            'slug' => 'harbour',
            'name' => ['en' => 'Harbour', 'ar' => 'الميناء'],
            'description' => ['en' => 'Light and airy'],
            'is_premium' => false,
        ]);

        $this->assertSame([
            'id' => $template->id,
            'slug' => 'harbour',
            'name' => ['en' => 'Harbour', 'ar' => 'الميناء'],
            'description' => ['en' => 'Light and airy', 'ar' => null],
            'thumbnail_url' => null,
            'is_premium' => false,
            'locked' => false,
            'settings_schema' => $schema,
        ], $this->resolve($template, $this->owner()->user));
    }

    public function test_a_premium_design_is_locked_without_the_flag(): void
    {
        $template = Template::factory()->create(['is_premium' => true]);

        $data = $this->resolve($template, $this->owner()->user);

        $this->assertTrue($data['is_premium']);
        $this->assertTrue($data['locked']);
    }

    public function test_a_premium_design_is_open_with_the_flag(): void
    {
        $this->defaultPackageIncludes(Feature::PremiumDesigns);
        $template = Template::factory()->create(['is_premium' => true]);

        $this->assertFalse($this->resolve($template, $this->owner()->user)['locked']);
    }

    /** Nobody signed in, or no restaurant yet: nothing to lock against. */
    public function test_without_a_restaurant_nothing_is_locked(): void
    {
        $template = Template::factory()->create(['is_premium' => true]);

        $this->assertFalse($this->resolve($template, null)['locked']);
        $this->assertFalse($this->resolve($template, $this->userWithoutRestaurant())['locked']);
    }

    public function test_a_fixed_design_has_an_empty_schema_and_a_thumbnail_comes_as_a_url(): void
    {
        $template = Template::factory()->create(['settings_schema' => null, 'description' => null]);
        $template->addMedia(UploadedFile::fake()->image('thumb.png'))->toMediaCollection('thumbnail');

        $data = $this->resolve($template->fresh(), null);

        $this->assertSame([], $data['settings_schema']);
        $this->assertSame(['en' => null, 'ar' => null], $data['description']);
        $this->assertStringEndsWith('thumb.png', $data['thumbnail_url']);
    }
}
