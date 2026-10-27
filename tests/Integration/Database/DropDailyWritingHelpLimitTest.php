<?php

namespace Tests\Integration\Database;

use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class DropDailyWritingHelpLimitTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_uses_a_day_leave_every_package_and_grant_and_the_rest_stays(): void
    {
        $pro = Package::findBySlug('pro');
        // As a package and a grant were saved before the uses a day went.
        DB::table('packages')->where('id', $pro->id)->update(['features' => json_encode([...(array) $pro->features, 'writing_help_limit' => 100])]);
        $restaurant = $this->owner();
        $base = ['restaurant_id' => $restaurant->id, 'source' => 'admin', 'created_at' => now(), 'updated_at' => now()];
        DB::table('feature_grants')->insert([
            [...$base, 'feature' => 'writing_help_limit', 'value' => 40],
            [...$base, 'feature' => 'dish_limit', 'value' => 10],
        ]);

        $migration = require database_path('migrations/2026_10_27_000003_drop_the_daily_writing_help_limit.php');
        $migration->up();

        $features = json_decode((string) DB::table('packages')->where('id', $pro->id)->value('features'), true);
        $this->assertArrayNotHasKey('writing_help_limit', $features);
        $this->assertSame(150, $features['dish_limit']);
        $this->assertSame(['dish_limit'], DB::table('feature_grants')->where('restaurant_id', $restaurant->id)->pluck('feature')->all());
    }
}
