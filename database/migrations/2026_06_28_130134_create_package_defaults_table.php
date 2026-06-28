<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The base/floor limits applied to a restaurant that has no granting package.
     * Previously the config/package.php defaults; now data so they're editable
     * without a deploy. Per-package values still live in template_feature, and
     * purchased slots still overlay via restaurant_features.
     */
    public function up(): void
    {
        Schema::create('package_defaults', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->unsignedInteger('value');
            $table->timestamps();
        });

        $now = now();

        DB::table('package_defaults')->insert([
            ['slug' => 'dish_limit', 'value' => 40, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'category_limit', 'value' => 10, 'created_at' => $now, 'updated_at' => $now],
            ['slug' => 'social_link_limit', 'value' => 4, 'created_at' => $now, 'updated_at' => $now],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('package_defaults');
    }
};
