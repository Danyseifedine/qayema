<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Packages & limits: templates are the plans, features are the sellable
     * units (booleans or limits), bundled via template_feature and granted to
     * restaurants via restaurant_features. Billing itself is handled by Cashier
     * Paddle, not this migration.
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

        Schema::create('template_feature', function (Blueprint $table) {
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('value')->default('1');
            $table->primary(['template_id', 'feature_id']);
        });

        Schema::create('restaurant_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('value')->default('1');
            $table->string('source', 12)->default('purchase');
            $table->timestamp('starts_at');
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'feature_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurant_features');
        Schema::dropIfExists('template_feature');
        Schema::dropIfExists('features');
    }
};
