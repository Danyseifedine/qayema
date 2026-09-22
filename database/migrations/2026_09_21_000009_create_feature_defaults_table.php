<?php

use App\Enums\Feature;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The floor every restaurant gets, editable from the admin panel — one row
     * per App\Enums\Feature. Limits hold their allowance; flags hold 0 or 1.
     * Per-restaurant exceptions are grants in `restaurant_features`, never edits
     * here, so changing a default moves every restaurant at once.
     */
    public function up(): void
    {
        Schema::create('feature_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('feature', 32)->unique();
            $table->unsignedInteger('value')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('feature_defaults')->insert(
            collect(Feature::cases())
                ->map(fn (Feature $feature): array => [
                    'feature' => $feature->value,
                    'value' => $feature->defaultValue(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ])
                ->all()
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_defaults');
    }
};
