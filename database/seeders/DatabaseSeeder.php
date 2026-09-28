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
     * Packages are seeded by their migration (from config('package.catalog')),
     * so a fresh database already has working limits before this runs. The
     * seeder below only fills a gap on a database that predates one.
     */
    public function run(): void
    {
        $this->call(PackageSeeder::class);
        $this->call(TemplateSeeder::class);

        User::updateOrCreate(['email' => 'admin@lebify.dev'], [
            'name' => 'Admin',
            'password' => Hash::make('qayema12332@@'),
            'role' => UserRole::Admin,
        ]);
    }
}
