<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Limits a package shows as unlimited while a fair-use number holds in
 * practice (App\Models\Package::isFairUse), and Premium's dishes and
 * categories becoming exactly that: "Unlimited" on screen, 1,000 each.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->json('fair_use')->nullable()->after('features');
        });

        $premium = DB::table('packages')->where('slug', 'premium')->first();

        if ($premium !== null) {
            $features = json_decode((string) $premium->features, true) ?: [];
            $features['dish_limit'] = 1000;
            $features['category_limit'] = 1000;

            DB::table('packages')->where('id', $premium->id)->update([
                'features' => json_encode($features),
                'fair_use' => json_encode(['dish_limit', 'category_limit']),
            ]);

            // Written past the model, so the cached limits are cleared here.
            \App\Services\Packages\Entitlements::flushAll();
        }
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('fair_use');
        });
    }
};
