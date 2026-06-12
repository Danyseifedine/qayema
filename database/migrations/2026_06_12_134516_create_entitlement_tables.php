<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Monetization & entitlements: templates are the plans, features are the
     * sellable units (booleans or limits), bundled via template_feature or
     * sold as add-ons; subscriptions/payments support manual billing first.
     */
    public function up(): void
    {
        Schema::create('features', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('kind', 10)->default('boolean');
            $table->boolean('is_addon')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('feature_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('period', 12);
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('template_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->string('period', 12);
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('template_feature', function (Blueprint $table) {
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('value')->default('1');
            $table->primary(['template_id', 'feature_id']);
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->morphs('subscribable');
            $table->string('period', 12);
            $table->decimal('price', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('status', 12)->default('active')->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('current_period_end');
            $table->timestamp('canceled_at')->nullable();
            $table->string('provider')->nullable();
            $table->string('provider_ref')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->morphs('payable');
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('USD');
            $table->string('status', 12)->default('pending')->index();
            $table->string('provider')->default('manual');
            $table->string('provider_ref')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('restaurant_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('value')->default('1');
            $table->string('source', 12)->default('purchase');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['restaurant_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_features');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('template_feature');
        Schema::dropIfExists('template_prices');
        Schema::dropIfExists('feature_prices');
        Schema::dropIfExists('features');
    }
};
