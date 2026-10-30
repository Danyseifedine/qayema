<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * When the owner and the admins were told the POS stopped answering, so
     * an outage is told once; cleared by the next pull that works.
     */
    public function up(): void
    {
        Schema::table('pos_connections', function (Blueprint $table) {
            $table->timestamp('alerted_at')->nullable()->after('problems');
        });
    }

    public function down(): void
    {
        Schema::table('pos_connections', function (Blueprint $table) {
            $table->dropColumn('alerted_at');
        });
    }
};
