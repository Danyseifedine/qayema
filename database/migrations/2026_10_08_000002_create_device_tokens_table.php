<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The phones that get notifications (Firebase Cloud Messaging): one row per
 * phone, the address Firebase gave it and whose phone it is. A phone that
 * signs in as someone else moves to them; one Firebase no longer knows is
 * deleted on the next send (App\Services\Push\PushSender).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // FCM tokens run to about 160 characters; 512 leaves room and
            // still fits a MySQL index in utf8mb4.
            $table->string('token', 512)->unique();
            $table->string('platform', 16);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
