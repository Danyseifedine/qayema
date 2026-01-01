<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the unique index using raw SQL if it exists
        // MySQL might be using it for the foreign key, so we need to handle it carefully
        try {
            DB::statement('ALTER TABLE `menu_settings` DROP INDEX `menu_settings_menu_id_key_unique`');
        } catch (\Exception $e) {
            // Index might not exist or already dropped
        }

        Schema::table('menu_settings', function (Blueprint $table) {
            // Drop columns that are now in settings table
            $table->dropColumn(['title', 'key', 'description', 'type']);

            // Add foreign key to settings table
            $table->foreignId('setting_id')->after('menu_id')->constrained()->onDelete('cascade');

            // Add unique constraint for menu_id + setting_id combination
            $table->unique(['menu_id', 'setting_id'], 'menu_settings_menu_id_setting_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('menu_settings', function (Blueprint $table) {
            // Drop unique constraint and foreign key
            $table->dropUnique('menu_settings_menu_id_setting_id_unique');
            $table->dropForeign(['setting_id']);
            $table->dropColumn('setting_id');

            // Restore old columns
            $table->string('title')->after('menu_id');
            $table->string('key')->after('title');
            $table->text('description')->nullable()->after('key');
            $table->string('type')->default('string')->after('value');

            // Restore unique constraint
            $table->unique(['menu_id', 'key'], 'menu_settings_menu_id_key_unique');
        });
    }
};
