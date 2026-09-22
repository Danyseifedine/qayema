<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Feature defaults are seeded by their migration (from App\Enums\Feature),
     * so a fresh database already has working limits before this runs.
     */
    public function run(): void
    {
        $this->call(TemplateSeeder::class);
        $this->call(CoinPackSeeder::class);

        User::updateOrCreate(['email' => 'admin@admin.com'], [
            'name' => 'Admin',
            'password' => Hash::make('password'),
            'role' => UserRole::Admin,
        ]);
    }
}
