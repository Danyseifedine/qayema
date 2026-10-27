<?php

namespace Tests\Integration\Support;

use App\Support\Asset;
use Tests\TestCase;

class AssetTest extends TestCase
{
    public function test_a_file_is_versioned_by_when_it_last_changed(): void
    {
        $this->assertSame(
            asset('js/menu-nav.js').'?v='.filemtime(public_path('js/menu-nav.js')),
            Asset::versioned('js/menu-nav.js'),
        );
    }

    public function test_a_missing_file_still_gets_a_version(): void
    {
        $this->assertSame(asset('js/not-there.js').'?v=1', Asset::versioned('js/not-there.js'));
    }
}
