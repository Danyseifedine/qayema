<?php

namespace Tests\Integration\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Only admins manage accounts, nobody deletes themselves, and the last admin
 * can never be removed, whether soft or for good.
 */
class UserPolicyTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_policy_is_registered_for_users(): void
    {
        $this->assertInstanceOf(UserPolicy::class, Gate::getPolicyFor(User::class));
    }

    public function test_an_admin_may_list_view_create_update_and_restore_any_account(): void
    {
        $admin = $this->admin();
        $owner = $this->owner()->user;

        $this->assertTrue($admin->can('viewAny', User::class));
        $this->assertTrue($admin->can('create', User::class));
        foreach ([$owner, $admin] as $model) {
            $this->assertTrue($admin->can('view', $model));
            $this->assertTrue($admin->can('update', $model));
            $this->assertTrue($admin->can('restore', $model));
        }
    }

    public function test_an_owner_may_do_nothing_to_any_account_including_their_own(): void
    {
        $owner = $this->owner()->user;
        $other = $this->owner()->user;

        $this->assertFalse($owner->can('viewAny', User::class));
        $this->assertFalse($owner->can('create', User::class));
        foreach ([$owner, $other] as $model) {
            foreach (['view', 'update', 'delete', 'restore', 'forceDelete'] as $ability) {
                $this->assertFalse($owner->can($ability, $model), "An owner must not {$ability}.");
            }
        }
    }

    public function test_an_admin_may_delete_and_force_delete_an_owner(): void
    {
        $admin = $this->admin();
        $owner = $this->owner()->user;

        $this->assertTrue($admin->can('delete', $owner));
        $this->assertTrue($admin->can('forceDelete', $owner));
    }

    public function test_an_admin_may_never_delete_themselves(): void
    {
        $admin = $this->admin();
        $this->admin();

        $this->assertFalse($admin->can('delete', $admin));
        $this->assertFalse($admin->can('forceDelete', $admin));
    }

    public function test_with_two_admins_either_may_remove_the_other(): void
    {
        $first = $this->admin();
        $second = $this->admin();

        $this->assertTrue($first->can('delete', $second));
        $this->assertTrue($second->can('forceDelete', $first));
    }

    public function test_a_soft_deleted_admin_no_longer_counts_towards_the_admins_left(): void
    {
        $first = $this->admin();
        $second = $this->admin();
        $second->delete();

        $this->assertSame(1, User::query()->where('role', UserRole::Admin)->count());
        $this->assertFalse($second->can('delete', $first), 'The survivor is the last admin.');
        $this->assertFalse($second->can('forceDelete', $first));
    }

    public function test_the_last_admin_is_protected_even_from_a_stale_admin_session(): void
    {
        $last = $this->admin();
        $demoted = $this->admin();
        User::query()->whereKey($demoted->id)->update(['role' => UserRole::MenuOwner->value]);

        $this->assertTrue($demoted->isAdmin(), 'Fixture: the in-memory instance still thinks it is an admin.');
        $this->assertFalse($demoted->can('delete', $last));
        $this->assertFalse($demoted->can('forceDelete', $last));
        $this->assertFalse((new UserPolicy)->delete($demoted, $last));
    }

    public function test_the_policy_called_directly_agrees_with_the_gate(): void
    {
        $admin = $this->admin();
        $owner = $this->owner()->user;
        $policy = new UserPolicy;

        $this->assertTrue($policy->viewAny($admin));
        $this->assertFalse($policy->viewAny($owner));
        $this->assertTrue($policy->view($admin, $owner));
        $this->assertFalse($policy->view($owner, $admin));
        $this->assertTrue($policy->create($admin));
        $this->assertFalse($policy->create($owner));
        $this->assertTrue($policy->update($admin, $owner));
        $this->assertFalse($policy->update($owner, $owner));
        $this->assertTrue($policy->restore($admin, $owner));
        $this->assertFalse($policy->restore($owner, $admin));
        $this->assertTrue($policy->forceDelete($admin, $owner));
        $this->assertFalse($policy->forceDelete($owner, $admin));
    }
}
