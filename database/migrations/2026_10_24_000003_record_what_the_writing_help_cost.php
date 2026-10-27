<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a restaurant's day of writing help cost in US dollars, worked out
 * when each request was made from the model's price then
 * (App\Services\Ai\WritingHelpPrice), so changing the model later never
 * re-prices the past. Days logged before this read 0.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('writing_help_days', function (Blueprint $table) {
            $table->decimal('cost', 12, 6)->default(0)->after('output_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('writing_help_days', fn (Blueprint $table) => $table->dropColumn('cost'));
    }
};
