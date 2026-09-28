<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Schemas\Components\Section;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What every admin form shares: styled dropdowns, full-width sections, and
 * image previews that load from this app rather than the storage bucket.
 */
class AdminFormsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_a_select_is_never_the_browsers_own_dropdown(): void
    {
        $this->assertFalse(Select::make('package')->isNative());
        $this->assertFalse(SelectFilter::make('package')->isNative());
    }

    public function test_a_section_spans_the_full_width_of_where_it_sits(): void
    {
        $this->assertSame(['default' => 'full'], array_filter(Section::make('Details')->getColumnSpan()));
    }

    public function test_the_edit_page_previews_an_image_from_this_app(): void
    {
        $restaurant = $this->owner();
        $media = $restaurant->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');
        $this->actingAs($this->admin());

        $page = Livewire::test(EditRestaurant::class, ['record' => $restaurant->getRouteKey()])->assertOk()->instance();
        $logo = collect($page->form->getFlatComponents())
            ->first(fn ($component): bool => $component instanceof SpatieMediaLibraryFileUpload && $component->getName() === 'logo');

        $files = $logo->getUploadedFiles();

        $this->assertSame(route('admin.media.preview', $media->uuid), reset($files)['url']);
    }

    public function test_an_admin_upload_is_stored_like_an_owners(): void
    {
        // Filament's own save used its default disk, the private `local` one,
        // so an image an admin uploaded never reached the menu.
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->getRouteKey()])
            ->fillForm(['logo' => UploadedFile::fake()->image('logo.png', 900, 900)])
            ->call('save')
            ->assertHasNoFormErrors();

        $media = $restaurant->fresh()->getFirstMedia('logo');
        $this->assertSame(config('media-library.disk_name'), $media->disk);
        $this->assertSame('image/webp', $media->mime_type);
        // The logo preset scales it down to fit 400 × 400.
        $this->assertLessThanOrEqual(400, getimagesize($media->getPath())[0]);
        $this->assertStringStartsWith('http', \App\Support\MediaUrl::of($restaurant->fresh(), 'logo'));
    }

    public function test_a_template_preview_uploaded_in_the_admin_is_stored_on_r2(): void
    {
        // Production's media disk is R2 (MEDIA_DISK=r2); faked here, so no
        // test ever reaches the real bucket.
        config(['media-library.disk_name' => 'r2']);
        Storage::fake('r2');
        $template = \App\Models\Template::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(\App\Filament\Admin\Resources\Templates\Pages\EditTemplate::class, ['record' => $template->getRouteKey()])
            ->fillForm(['thumbnail' => UploadedFile::fake()->image('preview.png', 1600, 1000)])
            ->call('save')
            ->assertHasNoFormErrors();

        $media = $template->fresh()->getFirstMedia('thumbnail');
        $this->assertSame('r2', $media->disk);
        $this->assertSame('image/webp', $media->mime_type);
        Storage::disk('r2')->assertExists($media->getPathRelativeToRoot());
        Storage::disk('local')->assertMissing($media->getPathRelativeToRoot());
    }

    public function test_a_dish_photo_from_the_admin_uses_the_dish_preset(): void
    {
        $restaurant = $this->owner();
        $dish = \App\Models\Dish::factory()->for($restaurant)->create();
        $this->actingAs($this->admin());

        Livewire::test(\App\Filament\Admin\Resources\Dishes\Pages\EditDish::class, ['record' => $dish->getRouteKey()])
            ->fillForm(['image' => UploadedFile::fake()->image('dish.png', 2400, 1800)])
            ->call('save')
            ->assertHasNoFormErrors();

        $media = $dish->fresh()->getFirstMedia('image');
        $this->assertSame('image/webp', $media->mime_type);
        $this->assertSame([1200, 900], array_slice(getimagesize($media->getPath()), 0, 2));
    }

    public function test_an_admin_gets_the_image_itself(): void
    {
        $restaurant = $this->owner();
        $media = $restaurant->addMedia(UploadedFile::fake()->image('logo.png', 10, 10))->toMediaCollection('logo');

        $response = $this->actingAs($this->admin())->get(route('admin.media.preview', $media->uuid));

        $response->assertOk()->assertHeader('Content-Type', 'image/png');
        $this->assertSame(file_get_contents($media->getPath()), $response->streamedContent());
    }

    public function test_only_an_admin_can_read_images_through_it(): void
    {
        $restaurant = $this->owner();
        $media = $restaurant->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');
        $url = route('admin.media.preview', $media->uuid);

        $this->get($url)->assertRedirect();
        $this->actingAs($restaurant->user)->get($url)->assertForbidden();
    }
}
