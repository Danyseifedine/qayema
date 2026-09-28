<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Storage;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every test writes its files to throwaway disks: uploads wait on `local`
     * (temp/) and media lands on `public` (MEDIA_DISK in phpunit.xml), so
     * nothing a test makes ever reaches real storage.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('public');
    }
}
