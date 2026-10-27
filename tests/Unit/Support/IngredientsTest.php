<?php

namespace Tests\Unit\Support;

use App\Support\Ingredients;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IngredientsTest extends TestCase
{
    /** @return array<string, array{0: string|null, 1: string, 2: string|null}> */
    public static function lists(): array
    {
        return [
            'dots, commas and etc' => ['onion,..tomato...ect', 'en', 'onion, tomato'],
            'already clean' => ['Lamb, rice, almonds', 'en', 'Lamb, rice, almonds'],
            'one per line with bullets' => ["• Lamb\n• Rice\r\n- Almonds", 'en', 'Lamb, Rice, Almonds'],
            'semicolons and spaces' => ['  lamb ;  rice;;  ', 'en', 'lamb, rice'],
            'repeats once' => ['Rice, rice, RICE, lamb', 'en', 'Rice, lamb'],
            'a sentence stays readable' => ['Slow-cooked lamb with rice, served with yogurt.', 'en', 'Slow-cooked lamb with rice, served with yogurt'],
            'a number keeps its dot' => ['Cheese 1.5 kg, bread', 'en', 'Cheese 1.5 kg, bread'],
            'ellipsis' => ['salt… pepper', 'en', 'salt, pepper'],
            'arabic separators' => ['بصل،طماطم؛ بقدونس... إلخ', 'ar', 'بصل، طماطم، بقدونس'],
            'chinese comma' => ['米饭、羊肉', 'zh', '米饭、羊肉'],
            'nothing but etc' => ['etc...', 'en', null],
            'blank' => ['   ', 'en', null],
            'null' => [null, 'en', null],
        ];
    }

    #[DataProvider('lists')]
    public function test_a_list_comes_out_tidy(?string $typed, string $locale, ?string $tidy): void
    {
        $this->assertSame($tidy, Ingredients::tidy($typed, $locale));
    }

    public function test_a_list_stops_at_its_limit(): void
    {
        $typed = implode(',', array_map(fn (int $i): string => "item {$i}", range(1, 60)));

        $this->assertCount(Ingredients::MAX_ITEMS, Ingredients::split($typed));
    }
}
