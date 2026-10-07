<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the restaurant last changed what an order holds (a dish added for a
 * table, a quantity fixed), so the guest following it and the owner's card
 * can both say so. Beside `guest_updated_at`, which is the guest's own.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('owner_updated_at')->nullable()->after('guest_updates');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('owner_updated_at');
        });
    }
};
