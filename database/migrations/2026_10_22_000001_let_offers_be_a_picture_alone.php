<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An offer may be a picture alone, its words drawn into it: `show_text`
 * off draws no title or text on the menu, so the title may be empty.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->json('title')->nullable()->change();
            $table->boolean('show_text')->default(true)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('show_text');
        });
    }
};
