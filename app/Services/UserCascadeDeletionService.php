<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserCascadeDeletionService
{
    /**
     * Permanently delete the user and all owned data (menus, dishes, categories, media, sessions, etc.).
     */
    public function delete(User $user): void
    {
        if (auth()->check() && auth()->id() === $user->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account from the admin panel.',
            ]);
        }

        if ($user->isAdmin()) {
            $adminCount = User::query()->where('role', UserRole::Admin)->count();
            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'user' => 'Cannot delete the last admin account.',
                ]);
            }
        }

        DB::transaction(function () use ($user): void {
            $this->deleteDatabaseSessions($user);
            $this->deletePasswordResetTokens($user);

            $menus = $user->menus()->with(['dishes', 'categories'])->get();

            foreach ($menus as $menu) {
                foreach ($menu->dishes as $dish) {
                    $dish->delete();
                }
                foreach ($menu->categories as $category) {
                    $category->delete();
                }

                $menu->socialLinks()->delete();
                $menu->statistics()->delete();
                $menu->settings()->delete();
                $menu->delete();
            }

            $user->clearMediaCollection('logo');
            $user->clearMediaCollection('cover_image');

            $user->deleteQuietly();
        });
    }

    private function deleteDatabaseSessions(User $user): void
    {
        if (DB::getSchemaBuilder()->hasTable('sessions')) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }
    }

    private function deletePasswordResetTokens(User $user): void
    {
        if (DB::getSchemaBuilder()->hasTable('password_reset_tokens')) {
            DB::table('password_reset_tokens')->where('email', $user->email)->delete();
        }
    }
}
