<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * What every end-to-end spec can count on. Packages come from their own
 * migration; this adds the two designs (Classic, and a premium one) and the
 * admin. Owners are built per test through POST /__e2e/scenario.
 */
class E2eSeeder extends Seeder
{
    public const ADMIN_EMAIL = 'admin@e2e.test';

    public const PASSWORD = 'e2e-password';

    public function run(): void
    {
        $this->call(TemplateSeeder::class);

        Template::query()->updateOrCreate(['slug' => 'midnight'], [
            'name' => ['en' => 'Midnight', 'ar' => 'منتصف الليل'],
            'description' => ['en' => 'A dark layout for evening places.', 'ar' => 'تصميم داكن للأماكن المسائية.'],
            'settings_schema' => array_map(
                fn (array $row): array => $row['key'] === 'primary_color' ? [...$row, 'default' => '#1F6FEB'] : $row,
                Template::CLASSIC_SCHEMA,
            ),
            'is_active' => true,
            'is_premium' => true,
            'sort_order' => 1,
        ]);

        User::query()->updateOrCreate(['email' => self::ADMIN_EMAIL], [
            'name' => 'E2E Admin',
            'password' => self::PASSWORD,
            'role' => UserRole::Admin,
            'onboarding_completed_at' => now(),
        ]);
    }
}
