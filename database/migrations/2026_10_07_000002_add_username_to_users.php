<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts made with a username and a password, next to the Google ones. Such
 * an account has no email at all, so users.email becomes optional, and a
 * package request from it lands in the contact inbox without an address.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Stored lowercase (App\Models\User::normalizeUsername), so the
            // unique index is case-insensitive in practice.
            $table->string('username', 30)->nullable()->unique()->after('name');
            $table->string('email')->nullable()->change();
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }
};
