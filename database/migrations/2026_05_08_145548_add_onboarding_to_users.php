<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->tinyInteger('onboarding_step')->default(0)->after('remember_token');
            $table->timestamp('onboarding_completed_at')->nullable()->after('onboarding_step');
        });

        // Existing users skip the wizard
        DB::table('users')->whereNull('onboarding_completed_at')->update([
            'onboarding_completed_at' => DB::raw('created_at'),
            'onboarding_step' => 3,
        ]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['onboarding_step', 'onboarding_completed_at']);
        });
    }
};
