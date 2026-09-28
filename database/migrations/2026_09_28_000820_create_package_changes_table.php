<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every change to a restaurant's package: what it moved from and to, the
     * dates it was given, and who did it. Written by PackageAssigner and by
     * the restaurant's own save hook, so no path skips it. Never edited.
     */
    public function up(): void
    {
        Schema::create('package_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('restaurant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->foreignId('to_package_id')->nullable()->constrained('packages')->nullOnDelete();
            $table->timestamp('starts_at')->nullable();
            // Null means forever.
            $table->timestamp('ends_at')->nullable();
            // Null when no one was signed in (a console command, a test).
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['restaurant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_changes');
    }
};
