<?php

namespace Tests\Feature\Pos;

use App\Services\Pos\PosAddress;
use Tests\TestCase;

/**
 * The addresses Qayema calls for a POS: tidied as the owner typed them, and
 * in production only public https ones (never Qayema's own network).
 */
class PosAddressTest extends TestCase
{
    public function test_an_address_is_kept_as_scheme_host_port_and_path(): void
    {
        $this->assertSame('https://pos.example.com', PosAddress::normalize(' pos.example.com/ '));
        $this->assertSame('https://pos.example.com:8443/shop', PosAddress::normalize('https://POS.example.com:8443/shop/?x=1#top'));
        $this->assertNull(PosAddress::normalize('ftp://pos.example.com'));
        $this->assertNull(PosAddress::normalize('https://user:pass@pos.example.com'));
        $this->assertNull(PosAddress::normalize(''));
    }

    public function test_local_development_may_call_this_machine(): void
    {
        $this->assertTrue(PosAddress::mayCall('http://127.0.0.1:8100'));
    }

    public function test_production_calls_only_public_https_addresses(): void
    {
        $this->app['env'] = 'production';
        config(['services.pos.allow_private' => false]);

        $this->assertFalse(PosAddress::mayCall('http://93.184.216.34'));
        $this->assertFalse(PosAddress::mayCall('https://127.0.0.1'));
        $this->assertFalse(PosAddress::mayCall('https://10.0.0.5/pos'));
        $this->assertFalse(PosAddress::mayCall('https://169.254.169.254'));
        $this->assertFalse(PosAddress::mayCall('https://[::1]'));
        $this->assertTrue(PosAddress::mayCall('https://93.184.216.34'));

        config(['services.pos.allow_private' => true]);
        $this->assertTrue(PosAddress::mayCall('http://10.0.0.5'));
    }
}
