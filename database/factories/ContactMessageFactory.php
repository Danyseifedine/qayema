<?php

namespace Database\Factories;

use App\Models\ContactMessage;
use App\Models\Package;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactMessage>
 */
class ContactMessageFactory extends Factory
{
    protected $model = ContactMessage::class;

    /**
     * A plain message from the public contact form.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'message' => fake()->paragraph(),
            'ip_address' => fake()->ipv4(),
            'user_id' => null,
            'package_id' => null,
        ];
    }

    /** An owner asking for a package from the dashboard. */
    public function packageRequest(?Package $package = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'user_id' => User::factory(),
            'package_id' => $package?->id ?? fn (): ?int => Package::findBySlug('pro')?->id,
        ]);
    }
}
