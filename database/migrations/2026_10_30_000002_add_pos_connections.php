<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Connect to POS: a restaurant linked to one POS branch, whose catalog
     * becomes the menu's categories and dishes. Each of those rows remembers
     * the POS id it came from; one gone from the POS is hidden, not deleted.
     */
    public function up(): void
    {
        Schema::create('pos_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->unique()->constrained()->cascadeOnDelete();
            // The POS's address, its key (sent as a bearer token) and the
            // secret its webhooks are signed with, both kept encrypted.
            $table->string('address', 255);
            $table->text('key');
            $table->text('signing_secret');
            // The POS's id for this link, named in every webhook.
            $table->uuid('pos_uuid')->unique();
            $table->string('company', 255)->nullable();
            $table->string('branch', 255)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('locales')->nullable();
            $table->boolean('sends_orders')->default(false);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_attempt_at')->nullable();
            $table->string('last_error', 500)->nullable();
            // What the last sync could not apply, listed for the owner.
            $table->json('problems')->nullable();
            $table->timestamps();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->string('pos_id', 64)->nullable();
            $table->timestamp('pos_removed_at')->nullable();
            $table->unique(['restaurant_id', 'pos_id']);
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->string('pos_id', 64)->nullable();
            $table->timestamp('pos_removed_at')->nullable();
            // Changes only when the POS photo does, so it is fetched once.
            $table->string('pos_image_version', 64)->nullable();
            $table->unique(['restaurant_id', 'pos_id']);
        });

        Schema::table('dish_variants', function (Blueprint $table) {
            $table->string('pos_id', 64)->nullable();
            $table->unique(['dish_id', 'pos_id']);
        });

        Schema::table('dish_variant_options', function (Blueprint $table) {
            $table->string('pos_id', 64)->nullable();
            $table->unique(['dish_variant_id', 'pos_id']);
        });

        Schema::table('dish_addon_groups', function (Blueprint $table) {
            $table->string('pos_id', 64)->nullable();
            $table->unique(['dish_id', 'pos_id']);
        });

        Schema::table('dish_addons', function (Blueprint $table) {
            $table->string('pos_id', 64)->nullable();
            $table->unique(['dish_id', 'pos_id']);
        });
    }

    public function down(): void
    {
        Schema::table('dish_addons', function (Blueprint $table) {
            $table->dropUnique(['dish_id', 'pos_id']);
            $table->dropColumn('pos_id');
        });

        Schema::table('dish_addon_groups', function (Blueprint $table) {
            $table->dropUnique(['dish_id', 'pos_id']);
            $table->dropColumn('pos_id');
        });

        Schema::table('dish_variant_options', function (Blueprint $table) {
            $table->dropUnique(['dish_variant_id', 'pos_id']);
            $table->dropColumn('pos_id');
        });

        Schema::table('dish_variants', function (Blueprint $table) {
            $table->dropUnique(['dish_id', 'pos_id']);
            $table->dropColumn('pos_id');
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->dropUnique(['restaurant_id', 'pos_id']);
            $table->dropColumn(['pos_id', 'pos_removed_at', 'pos_image_version']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['restaurant_id', 'pos_id']);
            $table->dropColumn(['pos_id', 'pos_removed_at']);
        });

        Schema::dropIfExists('pos_connections');
    }
};
