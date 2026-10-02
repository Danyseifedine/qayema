<?php

namespace Tests\Integration\Services\Qr;

use App\Models\Restaurant;
use App\Models\Template;
use App\Services\Qr\QrStyle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class QrStyleTest extends TestCase
{
    use CreatesOwners;
    use RefreshDatabase;

    /**
     * @return array{0: Restaurant, 1: Media}
     */
    private function withLogo(): array
    {
        $restaurant = $this->owner();
        $media = $restaurant->addMedia(UploadedFile::fake()->image('logo.png', 40, 40))->toMediaCollection('logo');

        return [$restaurant, $media];
    }

    public function test_the_logo_is_read_from_its_disk_once(): void
    {
        [$restaurant, $media] = $this->withLogo();
        $first = QrStyle::logoDataUrl($restaurant->fresh());

        // Gone from the disk, yet still drawn: the second answer is the kept one.
        Storage::disk($media->disk)->delete($media->getPathRelativeToRoot());

        $this->assertStringStartsWith('data:image/png;base64,', (string) $first);
        $this->assertSame($first, QrStyle::logoDataUrl($restaurant->fresh()));
    }

    public function test_no_logo_is_no_data_url(): void
    {
        $this->assertNull(QrStyle::logoDataUrl($this->owner()));
    }

    public function test_a_readable_logo_is_inlined_byte_for_byte(): void
    {
        [$restaurant, $media] = $this->withLogo();

        $bytes = Storage::disk($media->disk)->get($media->getPathRelativeToRoot());

        $this->assertIsString($bytes);
        $this->assertSame(
            'data:'.$media->mime_type.';base64,'.base64_encode($bytes),
            QrStyle::logoDataUrl($restaurant->fresh()),
        );
    }

    public function test_a_logo_of_exactly_one_megabyte_is_still_inlined(): void
    {
        [$restaurant, $media] = $this->withLogo();
        $media->forceFill(['size' => 1024 * 1024])->save();

        $this->assertStringStartsWith('data:image/png;base64,', (string) QrStyle::logoDataUrl($restaurant->fresh()));
    }

    public function test_a_logo_over_one_megabyte_is_left_out(): void
    {
        [$restaurant, $media] = $this->withLogo();
        $media->forceFill(['size' => 1024 * 1024 + 1])->save();

        $this->assertNull(QrStyle::logoDataUrl($restaurant->fresh()));
    }

    public function test_a_logo_whose_disk_cannot_be_reached_is_logged_and_left_out(): void
    {
        [$restaurant, $media] = $this->withLogo();
        $media->forceFill(['disk' => 'gone'])->save();

        Log::spy();

        $this->assertNull(QrStyle::logoDataUrl($restaurant->fresh()));

        Log::shouldHaveReceived('warning')->once()->with(
            'QR logo could not be read; drawing the code without it.',
            Mockery::on(fn (array $context): bool => $context['restaurant'] === $restaurant->id
                && $context['disk'] === 'gone'
                && str_contains($context['error'], 'gone')),
        );
    }

    public function test_a_logo_whose_file_is_missing_is_left_out_quietly(): void
    {
        [$restaurant, $media] = $this->withLogo();
        Storage::disk($media->disk)->delete($media->getPathRelativeToRoot());

        Log::spy();

        $this->assertNull(QrStyle::logoDataUrl($restaurant->fresh()));

        Log::shouldNotHaveReceived('warning');
    }

    public function test_an_empty_logo_file_is_left_out(): void
    {
        [$restaurant, $media] = $this->withLogo();
        Storage::disk($media->disk)->put($media->getPathRelativeToRoot(), '');

        $this->assertNull(QrStyle::logoDataUrl($restaurant->fresh()));
    }

    public function test_the_brand_colour_is_gold_without_a_design(): void
    {
        $this->assertSame(Template::DEFAULT_PRIMARY_COLOR, QrStyle::brandColor($this->owner()));
    }

    public function test_the_brand_colour_is_the_designs_primary_colour(): void
    {
        $template = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#123456'],
        ])->create();

        $this->assertSame('#123456', QrStyle::brandColor($this->owner(['template_id' => $template->id])));
    }

    public function test_a_primary_colour_that_is_not_hex_falls_back_to_gold(): void
    {
        $template = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => 'teal'],
        ])->create();

        $this->assertSame(Template::DEFAULT_PRIMARY_COLOR, QrStyle::brandColor($this->owner(['template_id' => $template->id])));
    }
}
