<?php

namespace Tests\Integration\Rules;

use App\Models\PreviousSlug;
use App\Rules\AvailableSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/** AvailableSlug::rules(): the one list every form that sets a menu link uses. */
class AvailableSlugTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function passes(mixed $value, ?int $restaurantId = null): bool
    {
        return Validator::make(['slug' => $value], ['slug' => AvailableSlug::rules($restaurantId)])->passes();
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function refused(): array
    {
        return [
            'empty' => [''],
            'one character' => ['a'],
            '101 characters' => [str_repeat('a', 101)],
            'capitals' => ['Beit-Rami'],
            'a space' => ['beit rami'],
            'starts with a dash' => ['-beit'],
            'ends with a dash' => ['beit-'],
            'other characters' => ['beit_rami!'],
            'not text' => [['beit-rami']],
            'reserved' => ['admin'],
        ];
    }

    #[DataProvider('refused')]
    public function test_a_link_that_does_not_fit_is_refused(mixed $slug): void
    {
        $this->assertFalse($this->passes($slug));
    }

    public function test_a_clean_free_link_up_to_100_characters_passes(): void
    {
        $this->assertTrue($this->passes('beit-rami-2'));
        $this->assertTrue($this->passes('ab'));
        $this->assertTrue($this->passes(str_repeat('a', AvailableSlug::MAX_LENGTH)));
    }

    public function test_another_restaurants_link_or_former_link_is_taken_but_its_own_is_not(): void
    {
        $theirs = $this->owner(['slug' => 'snack-lina']);
        PreviousSlug::query()->create(['restaurant_id' => $theirs->id, 'slug' => 'old-lina']);
        $mine = $this->owner(['slug' => 'beit-rami']);

        $this->assertFalse($this->passes('snack-lina', $mine->id));
        $this->assertFalse($this->passes('old-lina', $mine->id));
        $this->assertTrue($this->passes('beit-rami', $mine->id));
        $this->assertTrue($this->passes('old-lina', $theirs->id));
    }
}
