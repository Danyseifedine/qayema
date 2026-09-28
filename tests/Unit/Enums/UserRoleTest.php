<?php

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;
use ValueError;

class UserRoleTest extends TestCase
{
    public function test_a_user_is_an_admin_or_a_menu_owner(): void
    {
        $this->assertSame(['admin', 'menu_owner'], array_column(UserRole::cases(), 'value'));
        $this->assertSame(UserRole::Admin, UserRole::from('admin'));
        $this->assertSame(UserRole::MenuOwner, UserRole::from('menu_owner'));
    }

    public function test_roles_are_matched_exactly(): void
    {
        $this->assertNull(UserRole::tryFrom('owner'));
        $this->assertNull(UserRole::tryFrom('Admin'));
    }

    public function test_an_unknown_role_cannot_be_read(): void
    {
        $this->expectException(ValueError::class);

        UserRole::from('superuser');
    }
}
