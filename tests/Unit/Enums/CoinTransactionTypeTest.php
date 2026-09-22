<?php

namespace Tests\Unit\Enums;

use App\Enums\CoinTransactionType;
use Tests\TestCase;

class CoinTransactionTypeTest extends TestCase
{
    public function test_only_purchases_and_grants_are_credits(): void
    {
        $this->assertTrue(CoinTransactionType::Purchase->isCredit());
        $this->assertTrue(CoinTransactionType::AdminGrant->isCredit());
        $this->assertFalse(CoinTransactionType::Spend->isCredit());
        $this->assertFalse(CoinTransactionType::Refund->isCredit(), 'A refund takes coins back.');
    }

    public function test_every_case_has_a_label_in_both_locales(): void
    {
        foreach (['en', 'ar'] as $locale) {
            app()->setLocale($locale);
            foreach (CoinTransactionType::cases() as $type) {
                $this->assertNotSame('coins.'.$type->value, $type->label(), "{$type->value} missing in {$locale}");
            }
        }
    }

    public function test_options_cover_every_case(): void
    {
        $this->assertSame(
            array_map(fn (CoinTransactionType $t) => $t->value, CoinTransactionType::cases()),
            array_keys(CoinTransactionType::options()),
        );
    }
}
