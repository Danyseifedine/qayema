<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a WhatsApp order asks the guest for before WhatsApp opens: each of
 * their name, phone and (for a delivery) address off, optional or required,
 * for delivery and pickup apart from orders at the table. Null asks nothing,
 * as every WhatsApp order did until now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->json('whatsapp_fields')->nullable()->after('dine_in_mode');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('whatsapp_fields');
        });
    }
};
