<?php

namespace Tests\Unit\Services\Menu;

use App\Enums\BuildShape;
use App\Services\Menu\BuildPrice;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * What a build adds to its dish, the twin of the menu's buildPrice()
 * (public/js/menu-dish.js): the same cases must give the same cents there.
 */
class BuildPriceTest extends TestCase
{
    private const MARGHERITA = ['price' => '12.00', 'part_prices' => ['2' => '7.00']];

    private const PEPPERONI = ['price' => '16.00', 'part_prices' => null];

    private const VEGGIE = ['price' => '12.25', 'part_prices' => []];

    /** @return array<string, array{0: BuildShape, 1: list<array{price: string, part_prices: array<string, string>|null}>, 2: string}> */
    public static function cases(): array
    {
        return [
            'a whole pizza is its price' => [BuildShape::Split, [self::PEPPERONI], '16.00'],
            'two halves without set prices share the whole' => [BuildShape::Split, [self::PEPPERONI, self::VEGGIE], '14.13'],
            'a half the owner priced keeps that price' => [BuildShape::Split, [self::MARGHERITA, self::PEPPERONI], '15.00'],
            'both halves of the same' => [BuildShape::Split, [self::PEPPERONI, self::PEPPERONI], '16.00'],
            'thirds share once, rounded half up' => [BuildShape::Split, [self::VEGGIE, self::VEGGIE, self::PEPPERONI], '13.50'],
            'a price set for halves is not a third' => [BuildShape::Split, [self::MARGHERITA, self::PEPPERONI, self::PEPPERONI], '14.67'],
            'pieces add up whole' => [BuildShape::Pieces, [self::VEGGIE, self::VEGGIE, self::PEPPERONI], '40.50'],
            'part prices are no pieces\' business' => [BuildShape::Pieces, [self::MARGHERITA, self::MARGHERITA], '24.00'],
            'nothing built adds nothing' => [BuildShape::Split, [], '0.00'],
        ];
    }

    /** @param  list<array{price: string, part_prices: array<string, string>|null}>  $parts */
    #[DataProvider('cases')]
    public function test_a_build_is_priced_to_the_cent(BuildShape $shape, array $parts, string $price): void
    {
        $this->assertSame($price, BuildPrice::of($shape, $parts));
    }

    public function test_the_least_it_can_add_is_its_cheapest_count_and_choice(): void
    {
        // Whole: $12; halves: two set halves at $7 is $14. The whole wins.
        $this->assertSame('12.00', BuildPrice::cheapest(BuildShape::Split, [1, 2], [self::MARGHERITA, self::PEPPERONI]));
        // Halves only: two of Margherita's set halves.
        $this->assertSame('14.00', BuildPrice::cheapest(BuildShape::Split, [2], [self::MARGHERITA, self::PEPPERONI]));
        // Two to three scoops: two of the cheapest.
        $this->assertSame('24.00', BuildPrice::cheapest(BuildShape::Pieces, [2, 3], [self::MARGHERITA, self::PEPPERONI]));
        $this->assertSame('0.00', BuildPrice::cheapest(BuildShape::Split, [2], []));
    }

    public function test_a_set_price_is_only_for_a_part_size(): void
    {
        $this->assertSame('7.00', BuildPrice::setPrice(['2' => '7'], 2));
        $this->assertNull(BuildPrice::setPrice(['1' => '9.00'], 1), 'A whole is the choice\'s own price.');
        $this->assertNull(BuildPrice::setPrice(['2' => 'cheap'], 2));
        $this->assertNull(BuildPrice::setPrice(null, 3));
    }
}
