<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Templates are pure design and no longer bundle features. Every limit now
     * comes from the package_defaults floor (snapshotted per restaurant into
     * restaurant_features) plus purchased add-on grants, so the template_feature
     * pivot is dead weight and is dropped.
     */
    public function up(): void
    {
        Schema::dropIfExists('template_feature');
    }

    public function down(): void
    {
        Schema::create('template_feature', function (Blueprint $table) {
            $table->foreignId('template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('feature_id')->constrained()->cascadeOnDelete();
            $table->string('value')->default('1');
            $table->primary(['template_id', 'feature_id']);
        });
    }
};
