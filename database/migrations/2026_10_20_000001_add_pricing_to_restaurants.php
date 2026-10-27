<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What one restaurant pays, when it is not its package's price (a deal, a
 * discount), and whether it pays through Whish Money. The package history
 * keeps the price each change left in force.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            // Null: the package's own price. In the package's currency.
            $table->unsignedInteger('price_cents')->nullable()->after('package_ends_at');
            $table->boolean('pays_with_whish')->default(false)->after('price_cents');
        });

        Schema::table('package_changes', function (Blueprint $table) {
            $table->unsignedInteger('price_cents')->nullable()->after('ends_at');
        });
    }

    public function down(): void
    {
        Schema::table('package_changes', function (Blueprint $table) {
            $table->dropColumn('price_cents');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn(['price_cents', 'pays_with_whish']);
        });
    }
};
