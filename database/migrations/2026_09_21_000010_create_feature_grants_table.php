<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Grants stacked on top of the restaurant's package: limits add up, flags
     * switch on, and an unlimited package limit stays unlimited. `source`
     * records where a grant came from ('admin' when you bump one restaurant by
     * hand, 'purchase' when it was paid for).
     */
    public function up(): void
    {
        Schema::create('feature_grants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->string('feature', 32);
            $table->unsignedInteger('value')->default(1);
            $table->string('source', 12)->default('admin');
            // Why an admin gave it, for whoever reads the grant later.
            $table->string('note')->nullable();
            // Null means the grant never expires, which is the norm.
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->index(['restaurant_id', 'feature']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_grants');
    }
};
