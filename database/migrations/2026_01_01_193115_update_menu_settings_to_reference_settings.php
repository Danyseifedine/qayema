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
        // Check which columns actually exist using Schema facade
        $existingColumns = Schema::getColumnListing('menu_settings');

        // Drop the unique index using raw SQL if it exists
        try {
            DB::statement('ALTER TABLE `menu_settings` DROP INDEX `menu_settings_menu_id_key_unique`');
        } catch (\Exception $e) {
            // Index might not exist or already dropped
        }

        // Drop columns one by one if they exist
        if (in_array('title', $existingColumns)) {
            Schema::table('menu_settings', function (Blueprint $table) {
                $table->dropColumn('title');
            });
        }
        if (in_array('key', $existingColumns)) {
            Schema::table('menu_settings', function (Blueprint $table) {
                $table->dropColumn('key');
            });
        }
        if (in_array('description', $existingColumns)) {
            Schema::table('menu_settings', function (Blueprint $table) {
                $table->dropColumn('description');
            });
        }
        if (in_array('type', $existingColumns)) {
            Schema::table('menu_settings', function (Blueprint $table) {
                $table->dropColumn('type');
            });
        }

        // Re-check columns after dropping
        $existingColumns = Schema::getColumnListing('menu_settings');

        // Add foreign key to settings table (only if it doesn't exist)
        if (! in_array('setting_id', $existingColumns)) {
            Schema::table('menu_settings', function (Blueprint $table) {
                $table->foreignId('setting_id')->after('menu_id')->constrained()->onDelete('cascade');
            });
        }

        // Add unique constraint for menu_id + setting_id combination
        try {
            $indexes = DB::select("SHOW INDEXES FROM `menu_settings` WHERE Key_name = 'menu_settings_menu_id_setting_id_unique'");
            if (empty($indexes)) {
                Schema::table('menu_settings', function (Blueprint $table) {
                    $table->unique(['menu_id', 'setting_id'], 'menu_settings_menu_id_setting_id_unique');
                });
            }
        } catch (\Exception $e) {
            // Index might already exist
        }
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
