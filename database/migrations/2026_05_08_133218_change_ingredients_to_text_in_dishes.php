<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Change type first so MySQL accepts plain text updates
        Schema::table('dishes', function (Blueprint $table) {
            $table->text('ingredients')->nullable()->change();
        });

        // Convert existing JSON arrays to comma-separated text
        DB::table('dishes')->whereNotNull('ingredients')->orderBy('id')->each(function ($dish) {
            $decoded = json_decode($dish->ingredients, true);
            if (is_array($decoded)) {
                DB::table('dishes')->where('id', $dish->id)->update([
                    'ingredients' => implode(', ', array_filter($decoded)),
                ]);
            }
        });
    }

    public function down(): void
    {
        DB::table('dishes')->whereNotNull('ingredients')->orderBy('id')->each(function ($dish) {
            $items = array_values(array_filter(array_map('trim', explode(',', $dish->ingredients))));
            DB::table('dishes')->where('id', $dish->id)->update([
                'ingredients' => json_encode($items),
            ]);
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->json('ingredients')->nullable()->change();
        });
    }
};
