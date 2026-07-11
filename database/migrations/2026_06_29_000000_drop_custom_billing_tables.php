<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Removes the app's custom billing scaffolding now that Cashier Paddle owns
     * billing. Idempotent: on a fresh database these tables were never created,
     * so every step is guarded. Also promotes Cashier's table to the standard
     * `subscriptions` name on databases that were first migrated while it was
     * temporarily called `paddle_subscriptions`.
     */
    public function up(): void
    {
        if (Schema::hasColumn('restaurant_features', 'payment_id')) {
            Schema::table('restaurant_features', function (Blueprint $table) {
                $table->dropConstrainedForeignId('payment_id');
            });
        }

        Schema::dropIfExists('payments');
        Schema::dropIfExists('template_prices');
        Schema::dropIfExists('feature_prices');

        // Where Cashier was installed under the temporary `paddle_subscriptions`
        // name, drop the custom `subscriptions` table and give Cashier the
        // standard name it expects.
        if (Schema::hasTable('paddle_subscriptions')) {
            Schema::dropIfExists('subscriptions');
            Schema::rename('paddle_subscriptions', 'subscriptions');
        }
    }

    public function down(): void
    {
        // One-way cleanup — the custom billing tables are not restored.
    }
};
