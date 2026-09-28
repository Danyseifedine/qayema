<?php

namespace Tests\Unit\Enums;

use App\Enums\MenuEventType;
use PHPUnit\Framework\TestCase;

class MenuEventTypeTest extends TestCase
{
    public function test_the_types_menu_track_js_sends(): void
    {
        $this->assertSame(
            ['dish_add', 'category_open', 'search', 'search_miss', 'whatsapp', 'map', 'call', 'social', 'language'],
            array_map(fn (MenuEventType $type): string => $type->value, MenuEventType::cases()),
        );
    }

    public function test_only_a_dish_add_carries_a_dish(): void
    {
        foreach (MenuEventType::cases() as $type) {
            $this->assertSame($type === MenuEventType::DishAdd, $type->carriesDish(), $type->value);
        }
    }

    public function test_only_a_category_open_carries_a_category(): void
    {
        foreach (MenuEventType::cases() as $type) {
            $this->assertSame($type === MenuEventType::CategoryOpen, $type->carriesCategory(), $type->value);
        }
    }

    public function test_searches_socials_and_languages_carry_a_value(): void
    {
        $carrying = array_values(array_filter(MenuEventType::cases(), fn (MenuEventType $type): bool => $type->carriesValue()));

        $this->assertSame(
            [MenuEventType::Search, MenuEventType::SearchMiss, MenuEventType::Social, MenuEventType::Language],
            $carrying,
        );
    }

    /** A type carries one thing at most, so the recorder never keeps two fields. */
    public function test_no_type_carries_more_than_one_thing(): void
    {
        foreach (MenuEventType::cases() as $type) {
            $carried = (int) $type->carriesDish() + (int) $type->carriesCategory() + (int) $type->carriesValue();

            $this->assertLessThanOrEqual(1, $carried, $type->value);
        }
    }

    public function test_the_plain_taps_carry_nothing(): void
    {
        foreach ([MenuEventType::WhatsApp, MenuEventType::Map, MenuEventType::Call] as $type) {
            $this->assertFalse($type->carriesDish());
            $this->assertFalse($type->carriesCategory());
            $this->assertFalse($type->carriesValue());
        }
    }

    public function test_an_unknown_type_is_not_a_type(): void
    {
        $this->assertNull(MenuEventType::tryFrom('page_view'));
        $this->assertNull(MenuEventType::tryFrom('DISH_ADD'), 'Types are matched exactly.');
        $this->assertSame(MenuEventType::SearchMiss, MenuEventType::from('search_miss'));
    }
}
