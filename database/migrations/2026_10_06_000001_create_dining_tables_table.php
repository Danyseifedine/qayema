<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The restaurant's tables, for ordering from the seat.
 *
 * Each table has a QR code of its own, which opens the menu with the table's
 * `code`: a guest who scanned it can order "dine-in" and the order says which
 * table to bring it to. The code is random rather than the table's number, so
 * nobody orders to table 5 from home by changing a digit; the owner can give
 * a table a new one, and the old printed code stops working.
 *
 * An order keeps the table's name as it was (`table_name`), like its lines
 * keep their dishes' names: renaming or removing a table later never changes
 * what an order says.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dining_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);
            $table->string('code', 16)->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['restaurant_id', 'sort_order']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('table_id')->nullable()->after('fulfilment')->constrained('dining_tables')->nullOnDelete();
            $table->string('table_name', 40)->nullable()->after('table_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('table_id');
            $table->dropColumn('table_name');
        });

        Schema::dropIfExists('dining_tables');
    }
};
