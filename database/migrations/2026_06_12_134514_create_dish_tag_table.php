<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replace the free-form dishes.tags JSON column with a dish_tag pivot to
     * the curated tags table. Existing values are matched against tag slugs
     * best-effort; unmatched free-form strings are dropped by design.
     */
    public function up(): void
    {
        Schema::create('dish_tag', function (Blueprint $table) {
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['dish_id', 'tag_id']);
        });

        $tagIdsBySlug = DB::table('tags')->pluck('id', 'slug');

        DB::table('dishes')->whereNotNull('tags')->orderBy('id')->chunkById(200, function ($dishes) use ($tagIdsBySlug) {
            foreach ($dishes as $dish) {
                $values = json_decode($dish->tags ?? '[]', true) ?: [];

                foreach ($values as $value) {
                    $slug = \Illuminate\Support\Str::slug((string) $value);

                    if (isset($tagIdsBySlug[$slug])) {
                        DB::table('dish_tag')->insertOrIgnore([
                            'dish_id' => $dish->id,
                            'tag_id' => $tagIdsBySlug[$slug],
                        ]);
                    }
                }
            }
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }

    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('ingredients');
        });

        Schema::dropIfExists('dish_tag');
    }
};
