<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class UploadEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function upload(UploadedFile $file, string $context = 'dish')
    {
        return $this->actingAs($this->owner()->user)->post(
            route('api.uploads.temp'),
            ['file' => $file, 'context' => $context],
            ['Accept' => 'application/json'],
        );
    }

    public function test_a_file_over_ten_megabytes_is_rejected(): void
    {
        $this->upload(UploadedFile::fake()->image('huge.jpg')->size(10241))
            ->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_a_file_at_the_limit_is_accepted(): void
    {
        $this->upload(UploadedFile::fake()->image('ok.jpg', 100, 100)->size(10240))->assertOk();
    }

    public function test_a_file_php_itself_dropped_explains_that_it_was_too_large(): void
    {
        // PHP discards anything over `upload_max_filesize` before validation
        // runs and marks the upload UPLOAD_ERR_INI_SIZE. Laravel's `uploaded`
        // rule then fires, and without our message the owner would only see
        // the framework's "The file failed to upload."
        $path = UploadedFile::fake()->image('big.jpg', 100, 100)->getPathname();
        $dropped = new UploadedFile($path, 'big.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

        $this->upload($dropped)
            ->assertStatus(422)
            ->assertJsonValidationErrors('file')
            ->assertJsonFragment([
                'file' => ['That image is too large for the server to accept. Images must be 10 MB or smaller.'],
            ]);
    }

    public function test_a_body_over_the_post_limit_is_a_413_the_spa_can_read(): void
    {
        // The request never reaches a controller, so the handler has to name
        // this case or it surfaces as an opaque 'http_error'.
        $response = $this->actingAs($this->owner()->user)
            ->withServerVariables(['CONTENT_LENGTH' => (string) (1024 * 1024 * 1024)])
            ->post(route('api.uploads.temp'), [], ['Accept' => 'application/json']);

        $response->assertStatus(413)->assertJson([
            'message' => 'That upload is too large. Images must be 10 MB or smaller.',
            'code' => 'payload_too_large',
        ]);
    }

    public function test_a_decompression_bomb_is_rejected_by_its_declared_dimensions(): void
    {
        $this->upload(UploadedFile::fake()->image('bomb.png', 6001, 10))
            ->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_only_raster_photo_formats_are_accepted(): void
    {
        $this->upload(UploadedFile::fake()->create('anim.gif', 10, 'image/gif'))->assertStatus(422);
        $this->upload(UploadedFile::fake()->create('vector.svg', 10, 'image/svg+xml'))->assertStatus(422);
        $this->upload(UploadedFile::fake()->create('doc.pdf', 10, 'application/pdf'))->assertStatus(422);
    }

    public function test_a_php_file_wearing_a_jpg_name_is_rejected(): void
    {
        $spoof = UploadedFile::fake()->createWithContent('shell.jpg', '<?php echo "pwned"; ?>');

        $this->upload($spoof)->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_png_transparency_survives_optimisation(): void
    {
        $key = $this->upload(UploadedFile::fake()->image('logo.png', 300, 300), 'logo')->assertOk()->json('key');

        $path = storage_path('app/temp/'.$this->lastUserId().'/'.$key.'.webp');
        $this->assertFileExists($path);
        $this->assertSame('image/webp', mime_content_type($path));
    }

    public function test_the_removed_category_context_is_no_longer_accepted(): void
    {
        $this->upload(UploadedFile::fake()->image('x.jpg'), 'category')
            ->assertStatus(422)->assertJsonValidationErrors('context');
    }

    public function test_a_missing_context_falls_back_to_generic(): void
    {
        $this->actingAs($this->owner()->user)
            ->post(route('api.uploads.temp'), ['file' => UploadedFile::fake()->image('x.jpg', 100, 100)], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonStructure(['key', 'original_size', 'optimized_size', 'saved_percent']);
    }

    public function test_the_upload_limiter_bites_at_twenty_one_and_feeds_the_ban(): void
    {
        $user = $this->owner()->user;
        $status = null;

        for ($i = 0; $i < 25 && $status !== 429; $i++) {
            $status = $this->actingAs($user)->post(
                route('api.uploads.temp'),
                ['file' => UploadedFile::fake()->image("f{$i}.jpg", 20, 20), 'context' => 'dish'],
                ['Accept' => 'application/json'],
            )->getStatusCode();
        }

        $this->assertSame(429, $status);
        $this->assertSame(21, $i, 'Twenty uploads a minute, then the ceiling.');
    }

    public function test_a_guest_cannot_upload(): void
    {
        $this->post(route('api.uploads.temp'), ['file' => UploadedFile::fake()->image('x.jpg')], ['Accept' => 'application/json'])
            ->assertUnauthorized();
    }

    private function lastUserId(): int
    {
        return (int) \App\Models\User::query()->max('id');
    }
}
