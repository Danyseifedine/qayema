<?php

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Encrypt any tokens still stored as plaintext so they decrypt cleanly
     * under the new `encrypted` casts on SocialAccount. Rows that already
     * decrypt (or are empty) are left untouched, making this safe to re-run.
     */
    public function up(): void
    {
        DB::table('social_accounts')
            ->select(['id', 'access_token', 'refresh_token'])
            ->orderBy('id')
            ->chunkById(200, function ($accounts): void {
                foreach ($accounts as $account) {
                    $update = [];

                    foreach (['access_token', 'refresh_token'] as $column) {
                        $value = $account->{$column};

                        if (blank($value) || $this->isEncrypted($value)) {
                            continue;
                        }

                        $update[$column] = Crypt::encryptString($value);
                    }

                    if ($update !== []) {
                        DB::table('social_accounts')->where('id', $account->id)->update($update);
                    }
                }
            });
    }

    /**
     * Decrypt tokens back to plaintext so the column is readable without casts.
     */
    public function down(): void
    {
        DB::table('social_accounts')
            ->select(['id', 'access_token', 'refresh_token'])
            ->orderBy('id')
            ->chunkById(200, function ($accounts): void {
                foreach ($accounts as $account) {
                    $update = [];

                    foreach (['access_token', 'refresh_token'] as $column) {
                        $value = $account->{$column};

                        if (blank($value) || ! $this->isEncrypted($value)) {
                            continue;
                        }

                        $update[$column] = Crypt::decryptString($value);
                    }

                    if ($update !== []) {
                        DB::table('social_accounts')->where('id', $account->id)->update($update);
                    }
                }
            });
    }

    private function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);

            return true;
        } catch (DecryptException) {
            return false;
        }
    }
};
