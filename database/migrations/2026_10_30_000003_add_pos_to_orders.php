<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An order placed in the menu of a restaurant that sends its orders to
     * its POS: where sending it stands (sending, sent, failed), the POS's
     * number for it, why it could not be sent, and why the POS called it off.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('pos_reference', 64)->nullable();
            $table->string('pos_status', 20)->nullable();
            $table->string('pos_number', 30)->nullable();
            $table->string('pos_error', 500)->nullable();
            $table->string('cancel_reason', 255)->nullable();

            $table->index(['restaurant_id', 'pos_reference']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['restaurant_id', 'pos_reference']);
            $table->dropColumn(['pos_reference', 'pos_status', 'pos_number', 'pos_error', 'cancel_reason']);
        });
    }
};
