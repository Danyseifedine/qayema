<?php

namespace Tests\Integration\Enums;

use App\Enums\OrderStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Lang;
use Tests\TestCase;

class OrderStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_states_and_no_more(): void
    {
        $this->assertSame(['placed', 'done', 'cancelled'], array_column(OrderStatus::cases(), 'value'));
        $this->assertNull(OrderStatus::tryFrom('preparing'));
    }

    public function test_every_status_is_written_in_english_and_arabic(): void
    {
        foreach (['en', 'ar'] as $locale) {
            foreach (OrderStatus::cases() as $status) {
                $this->assertTrue(Lang::has('orders.'.$status->value, $locale, false), "No {$locale} label for {$status->value}.");
            }
        }
    }

    public function test_english_labels(): void
    {
        app()->setLocale('en');

        $this->assertSame('New', OrderStatus::Placed->label());
        $this->assertSame('Done', OrderStatus::Done->label());
        $this->assertSame('Cancelled', OrderStatus::Cancelled->label());
    }

    public function test_arabic_labels(): void
    {
        app()->setLocale('ar');

        $this->assertSame('جديد', OrderStatus::Placed->label());
        $this->assertSame('منتهي', OrderStatus::Done->label());
        $this->assertSame('ملغى', OrderStatus::Cancelled->label());
    }

    public function test_options_are_value_to_label_in_case_order(): void
    {
        app()->setLocale('en');

        $this->assertSame(['placed' => 'New', 'done' => 'Done', 'cancelled' => 'Cancelled'], OrderStatus::options());

        app()->setLocale('ar');

        $this->assertSame(['placed' => 'جديد', 'done' => 'منتهي', 'cancelled' => 'ملغى'], OrderStatus::options());
    }
}
