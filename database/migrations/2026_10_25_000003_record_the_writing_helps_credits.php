<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The AI credits a restaurant's day of writing help took, and its uses and
 * cost split by what was asked (a translation or a description), so the
 * admin sees what each really costs against its price in credits. Days
 * logged before this read 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('writing_help_days', function (Blueprint $table) {
            $table->unsignedInteger('credits')->default(0)->after('cost');
            $table->unsignedInteger('translation_uses')->default(0)->after('credits');
            $table->decimal('translation_cost', 12, 6)->default(0)->after('translation_uses');
            $table->unsignedInteger('description_uses')->default(0)->after('translation_cost');
            $table->decimal('description_cost', 12, 6)->default(0)->after('description_uses');
        });
    }

    public function down(): void
    {
        Schema::table('writing_help_days', fn (Blueprint $table) => $table->dropColumn([
            'credits', 'translation_uses', 'translation_cost', 'description_uses', 'description_cost',
        ]));
    }
};
