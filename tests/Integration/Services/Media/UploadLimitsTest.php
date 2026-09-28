<?php

namespace Tests\Integration\Services\Media;

use App\Services\Media\UploadLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `upload_max_filesize` and `post_max_size` are PHP_INI_PERDIR, so a test
 * cannot lower them: which branch of the local-development message applies
 * depends on the PHP running the suite, and each test says what it expects
 * for whichever that is.
 */
class UploadLimitsTest extends TestCase
{
    use RefreshDatabase;

    private function generic(): string
    {
        return 'That image is too large. Images must be '.UploadLimits::describe().' or smaller.';
    }

    private function devServer(): string
    {
        return 'This server only accepts images up to '.UploadLimits::describe().'. Start the API with `composer serve` to raise it.';
    }

    public function test_outside_local_development_the_message_names_only_the_limit(): void
    {
        $this->assertFalse($this->app->environment('local'));

        $this->assertSame($this->generic(), UploadLimits::tooLargeMessage());
    }

    public function test_in_local_development_a_low_server_limit_is_named_as_the_cause(): void
    {
        $this->app['env'] = 'local';

        $this->assertSame(
            UploadLimits::serverIsBelowApp() ? $this->devServer() : $this->generic(),
            UploadLimits::tooLargeMessage(),
        );
    }

    public function test_the_message_is_translated_for_arabic(): void
    {
        app()->setLocale('ar');

        $message = UploadLimits::tooLargeMessage();

        $this->assertNotSame($this->generic(), $message);
        $this->assertStringContainsString(UploadLimits::describe(), $message);
    }
}
