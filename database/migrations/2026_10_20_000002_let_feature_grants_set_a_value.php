<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A grant either adds to the package (`add`, as every grant did) or sets the
 * feature to its value for this restaurant whatever the package says
 * (`set`): a lower or higher limit, unlimited (a null value), or an on/off
 * feature turned off (0) or on (1).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feature_grants', function (Blueprint $table) {
            $table->string('mode', 8)->default('add')->after('feature');
            $table->unsignedInteger('value')->nullable()->default(1)->change();
        });
    }

    public function down(): void
    {
        Schema::table('feature_grants', function (Blueprint $table) {
            $table->dropColumn('mode');
            $table->unsignedInteger('value')->default(1)->change();
        });
    }
};
