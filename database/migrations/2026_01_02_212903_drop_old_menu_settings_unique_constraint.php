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
        // Drop the old unique constraint that only enforces uniqueness on menu_id
        // This constraint prevents multiple settings per menu, which is incorrect
        try {
            DB::statement('ALTER TABLE `menu_settings` DROP INDEX `menu_settings_menu_id_key_unique`');
        } catch (\Exception $e) {
            // Index might not exist, which is fine
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Note: We don't restore this constraint as it was incorrect
        // The correct constraint is menu_settings_menu_id_setting_id_unique
    }
};
