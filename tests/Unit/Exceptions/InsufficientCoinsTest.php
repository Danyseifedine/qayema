<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\InsufficientCoins;
use Tests\TestCase;

class InsufficientCoinsTest extends TestCase
{
    public function test_it_reports_the_shortfall(): void
    {
        $e = new InsufficientCoins(needed: 650, balance: 200);

        $this->assertSame(450, $e->shortfall());
        $this->assertStringContainsString('650', $e->getMessage());
        $this->assertStringContainsString('200', $e->getMessage());
    }

    public function test_the_shortfall_never_goes_negative(): void
    {
        $this->assertSame(0, (new InsufficientCoins(needed: 100, balance: 500))->shortfall());
        $this->assertSame(0, (new InsufficientCoins(needed: 100, balance: 100))->shortfall());
    }

    public function test_the_message_is_localised(): void
    {
        app()->setLocale('ar');

        $this->assertStringContainsString('كوينز', (new InsufficientCoins(10, 0))->getMessage());
    }
}
