<?php

namespace Tests\Unit\Enums;

use App\Enums\FeatureKind;
use PHPUnit\Framework\TestCase;

class FeatureKindTest extends TestCase
{
    public function test_a_feature_is_a_limit_or_a_flag(): void
    {
        $this->assertSame(['limit', 'flag'], array_column(FeatureKind::cases(), 'value'));
        $this->assertSame(FeatureKind::Limit, FeatureKind::from('limit'));
        $this->assertSame(FeatureKind::Flag, FeatureKind::from('flag'));
        $this->assertNull(FeatureKind::tryFrom('toggle'));
    }
}
