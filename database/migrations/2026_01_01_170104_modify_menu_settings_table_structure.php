<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('menu_settings', function (Blueprint $table) {
            // Drop old columns
            $table->dropColumn(['theme_color', 'currency', 'language']);

            // Add new columns
            $table->string('title')->after('menu_id');
            $table->string('key')->after('title');
            $table->text('description')->nullable()->after('key');
            $table->text('value')->nullable()->after('description');
            $table->string('type')->default('string')->after('value');

            // Add unique constraint for menu_id + key combination
            $table->unique(['menu_id', 'key'], 'menu_settings_menu_id_key_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_settings', function (Blueprint $table) {
            // Drop new columns and unique constraint
            $table->dropUnique('menu_settings_menu_id_key_unique');
            $table->dropColumn(['title', 'key', 'description', 'value', 'type']);

            // Restore old columns
            $table->string('theme_color')->default('#3b82f6')->after('menu_id');
            $table->string('currency')->default('USD')->after('theme_color');
            $table->string('language')->default('en')->after('currency');
        });
    }
};
