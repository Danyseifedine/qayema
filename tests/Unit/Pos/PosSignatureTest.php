<?php

namespace Tests\Unit\Pos;

use App\Services\Pos\PosSignature;
use PHPUnit\Framework\TestCase;

/**
 * The POS's webhook signature: an HMAC of the timestamp and the body, refused
 * when it does not match or is more than five minutes off.
 */
class PosSignatureTest extends TestCase
{
    public function test_a_signature_made_with_the_secret_is_accepted(): void
    {
        $signature = PosSignature::sign('secret', 1_800_000_000, '{"event":"catalog.changed"}');

        $this->assertStringStartsWith('sha256=', $signature);
        $this->assertTrue(PosSignature::verify('secret', '1800000000', $signature, '{"event":"catalog.changed"}', 1_800_000_100));
    }

    public function test_another_secret_another_body_a_stale_time_or_nothing_is_refused(): void
    {
        $signature = PosSignature::sign('secret', 1_800_000_000, 'body');

        $this->assertFalse(PosSignature::verify('other', '1800000000', $signature, 'body', 1_800_000_000));
        $this->assertFalse(PosSignature::verify('secret', '1800000000', $signature, 'changed', 1_800_000_000));
        $this->assertFalse(PosSignature::verify('secret', '1800000000', $signature, 'body', 1_800_000_301));
        $this->assertFalse(PosSignature::verify('secret', '1800000000', $signature, 'body', 1_799_999_699));
        $this->assertFalse(PosSignature::verify('secret', null, $signature, 'body', 1_800_000_000));
        $this->assertFalse(PosSignature::verify('secret', '18e8', $signature, 'body', 1_800_000_000));
        $this->assertFalse(PosSignature::verify('secret', '1800000000', null, 'body', 1_800_000_000));
    }
}
