<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A second currency beside the main one (LBP and USD), with the rate
 * between them: `exchange_rate` is how many of the main currency one of
 * the second is worth (89000 for 1 USD in LBP). A dish is priced in the
 * main currency (`currency` null) or the second one, its choices with it.
 * An order keeps the rate it was placed at, and each line its currency;
 * its total is in the main currency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->char('second_currency', 3)->nullable()->after('currency');
            $table->decimal('exchange_rate', 24, 12)->nullable()->after('second_currency');
        });

        Schema::table('dishes', function (Blueprint $table) {
            $table->char('currency', 3)->nullable()->after('price');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('exchange_rate', 24, 12)->nullable()->after('currency');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->char('currency', 3)->nullable()->after('line_total');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('exchange_rate'));
        Schema::table('dishes', fn (Blueprint $table) => $table->dropColumn('currency'));
        Schema::table('restaurants', fn (Blueprint $table) => $table->dropColumn(['second_currency', 'exchange_rate']));
    }
};
