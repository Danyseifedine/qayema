<?php

namespace Tests\Integration\Rules;

use App\Models\User;
use App\Rules\Username;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UsernameTest extends TestCase
{
    use RefreshDatabase;

    private function passes(mixed $value, ?int $userId = null): bool
    {
        return Validator::make(['username' => $value], ['username' => [new Username($userId)]])->passes();
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function goodUsernames(): array
    {
        return [
            'letters' => ['rami'],
            'three characters' => ['abc'],
            'thirty characters' => [str_repeat('a', 30)],
            'dots, dashes and underscores inside' => ['beit.rami_2-go'],
            'upper case, read lowercase' => ['BeitRami'],
            'spaces around, trimmed' => ['  rami  '],
        ];
    }

    #[DataProvider('goodUsernames')]
    public function test_a_good_username_passes(string $username): void
    {
        $this->assertTrue($this->passes($username));
    }

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function badUsernames(): array
    {
        return [
            'too short' => ['ab'],
            'too long' => [str_repeat('a', 31)],
            'an email' => ['rami@example.com'],
            'a space inside' => ['beit rami'],
            'starts with a dot' => ['.rami'],
            'ends with a dash' => ['rami-'],
            'arabic letters' => ['رامي'],
            'not text' => [['rami']],
        ];
    }

    #[DataProvider('badUsernames')]
    public function test_a_bad_username_fails(mixed $username): void
    {
        $this->assertFalse($this->passes($username));
    }

    public function test_another_accounts_username_is_taken_whatever_its_case(): void
    {
        User::factory()->withUsername('rami')->create();

        $this->assertFalse($this->passes('Rami'));
    }

    public function test_an_account_keeps_its_own_username(): void
    {
        $user = User::factory()->withUsername('rami')->create();

        $this->assertTrue($this->passes('rami', $user->id));
    }

    public function test_the_model_stores_it_lowercase_and_blank_as_null(): void
    {
        $this->assertSame('beit.rami', User::factory()->withUsername('  Beit.Rami ')->create()->username);
        $this->assertNull(User::factory()->create(['username' => ''])->username);
    }
}
