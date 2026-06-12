<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * v2 hygiene: one restaurant per user, sized char columns, per-restaurant
     * timezone, and the owner-chosen content base language (default_locale,
     * ar|en) replacing the old free-form preferred_language.
     */
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->unique('user_id');
            $table->string('timezone', 64)->default('UTC')->after('preferred_language');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->char('country_code', 2)->nullable()->change();
            $table->char('currency', 3)->default('USD')->nullable()->change();
        });

        DB::table('restaurants')
            ->whereNotIn('preferred_language', ['ar', 'en'])
            ->update(['preferred_language' => 'en']);

        Schema::table('restaurants', function (Blueprint $table) {
            $table->renameColumn('preferred_language', 'default_locale');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->char('default_locale', 2)->default('ar')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->renameColumn('default_locale', 'preferred_language');
        });

        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('preferred_language', 10)->default('en')->nullable()->change();
            $table->string('country_code', 5)->nullable()->change();
            $table->string('currency', 10)->default('USD')->nullable()->change();
            $table->dropColumn('timezone');
            $table->dropUnique(['user_id']);
        });
    }
};
