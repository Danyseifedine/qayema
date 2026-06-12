<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Scan uploads now go through the media library (collection "scan" on the
     * MenuScan model) so the app has a single file-storage convention.
     * Pending scans mid-flight during deploy will fail and can be retried.
     */
    public function up(): void
    {
        Schema::table('menu_scans', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('menu_scans', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('status');
        });
    }
};
