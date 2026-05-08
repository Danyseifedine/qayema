<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('dishes')->whereNotNull('ingredients')->orderBy('id')->each(function ($dish) {
            $items = array_values(array_filter(array_map('trim', explode("\n", $dish->ingredients))));
            DB::table('dishes')->where('id', $dish->id)->update([
                'ingredients' => json_encode($items),
            ]);
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->json('ingredients')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table) {
            $table->text('ingredients')->nullable()->change();
        });
    }
};
