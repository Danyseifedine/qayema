<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The `ip_address` column on contact_messages is queried for abuse throttling
     * but was created without an index. Add it only where missing so this is safe
     * to re-run across drivers.
     */
    public function up(): void
    {
        if (! Schema::hasIndex('contact_messages', ['ip_address'])) {
            Schema::table('contact_messages', function (Blueprint $table): void {
                $table->index('ip_address');
            });
        }
    }

    public function down(): void
    {
        $indexName = 'contact_messages_ip_address_index';

        if (Schema::hasIndex('contact_messages', $indexName)) {
            Schema::table('contact_messages', function (Blueprint $table) use ($indexName): void {
                $table->dropIndex($indexName);
            });
        }
    }
};
