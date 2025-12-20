<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_statistics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->onDelete('cascade');
            $table->string('session_id')->index();
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->string('referrer')->nullable();
            $table->string('country', 2)->nullable();
            $table->string('device_type')->nullable(); // mobile, desktop, tablet
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->timestamp('viewed_at');
            $table->timestamp('left_at')->nullable();
            $table->integer('time_spent')->nullable()->comment('Time spent in seconds');
            $table->integer('page_views')->default(1)->comment('Number of page views in this session');
            $table->json('interactions')->nullable()->comment('Track clicks, scrolls, etc.');
            $table->timestamps();

            $table->index(['menu_id', 'viewed_at']);
            $table->index(['session_id', 'menu_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_statistics');
    }
};
