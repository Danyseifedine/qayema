<?php

namespace Tests\Feature\Media;

use App\Models\User;
use App\Services\Media\UploadLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The web copy of the temp upload (`POST /temp-upload`), which the onboarding
 * wizard's logo and cover dropzones post to from a session page.
 */
class TempUploadWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->post(route('temp-upload'), ['file' => UploadedFile::fake()->image('logo.png', 100, 100)])
            ->assertRedirect(route('login'));

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_guest_asking_for_json_gets_a_401(): void
    {
        $this->postJson(route('temp-upload'), ['file' => UploadedFile::fake()->image('logo.png', 100, 100)])
            ->assertUnauthorized()
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    public function test_an_upload_is_optimized_and_parked_under_the_users_temp_folder(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('temp-upload'), [
                'file' => UploadedFile::fake()->image('logo.png', 400, 300),
                'context' => 'logo',
            ])
            ->assertOk()
            ->assertJsonStructure(['key', 'original_size', 'optimized_size', 'saved_percent']);

        $key = $response->json('key');

        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $key);
        $this->assertIsString($response->json('original_size'));
        $this->assertIsString($response->json('optimized_size'));
        $this->assertIsInt($response->json('saved_percent'));
        $this->assertSame(["temp/{$user->id}/{$key}.webp"], Storage::disk('local')->allFiles());
    }

    public function test_the_context_is_optional(): void
    {
        $user = User::factory()->create();

        $key = $this->actingAs($user)
            ->postJson(route('temp-upload'), ['file' => UploadedFile::fake()->image('photo.jpg', 200, 200)])
            ->assertOk()
            ->json('key');

        Storage::disk('local')->assertExists("temp/{$user->id}/{$key}.webp");
    }

    public function test_a_missing_file_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('temp-upload'), ['context' => 'logo'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'Please choose an image to upload.']);

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_file_that_is_not_an_image_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('temp-upload'), ['file' => UploadedFile::fake()->create('menu.pdf', 50, 'application/pdf')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => 'The upload must be an image.']);

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_file_over_the_limit_is_refused_with_the_limit_named(): void
    {
        $kilobytes = UploadLimits::APP_MAX_BYTES / 1024 + 1;

        $this->actingAs(User::factory()->create())
            ->postJson(route('temp-upload'), ['file' => UploadedFile::fake()->image('huge.jpg', 100, 100)->size($kilobytes)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file' => UploadLimits::tooLargeMessage()]);

        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_an_unknown_context_is_refused(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('temp-upload'), [
                'file' => UploadedFile::fake()->image('logo.png', 100, 100),
                'context' => 'avatar',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['context' => 'Invalid upload context.']);
    }

    /** Without JSON the dropzone's form falls back to the session's error bag. */
    public function test_a_plain_form_post_gets_its_errors_in_the_session(): void
    {
        $this->actingAs(User::factory()->create())
            ->from(route('onboarding'))
            ->post(route('temp-upload'), [])
            ->assertRedirect(route('onboarding'))
            ->assertSessionHasErrors(['file' => 'Please choose an image to upload.']);
    }

    /**
     * Unlike the menu's event limiter, `uploads` feeds the abuse auto-ban:
     * past 20 a minute every refused upload is a strike, and 20 strikes ban
     * the IP.
     */
    public function test_hammering_the_upload_limit_bans_the_ip(): void
    {
        config(['security.trusted_ips' => []]);
        $this->freezeTime();
        $user = User::factory()->create();
        $key = md5('uploads'.$user->id);

        for ($i = 0; $i < 20; $i++) {
            RateLimiter::hit($key, 60);
        }

        for ($strike = 1; $strike < 20; $strike++) {
            $this->actingAs($user)->postJson(route('temp-upload'), [])
                ->assertStatus(429)
                ->assertJsonPath('code', 'too_many_requests');
        }
        $this->assertDatabaseCount('blocked_ips', 0);

        $this->actingAs($user)->post(route('temp-upload'), [])->assertStatus(429);

        $this->assertDatabaseHas('blocked_ips', ['ip' => '127.0.0.1', 'reason' => 'auto: sustained abuse']);
    }
}
