<?php

namespace Tests\Unit\Enums;

use App\Enums\PackageStatus;
use PHPUnit\Framework\TestCase;

class PackageStatusTest extends TestCase
{
    public function test_a_package_is_active_scheduled_or_expired(): void
    {
        $this->assertSame(['active', 'scheduled', 'expired'], array_column(PackageStatus::cases(), 'value'));
        $this->assertSame(PackageStatus::Scheduled, PackageStatus::from('scheduled'));
        $this->assertNull(PackageStatus::tryFrom('cancelled'));
    }
}
