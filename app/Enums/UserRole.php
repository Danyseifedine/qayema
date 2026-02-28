<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case MenuOwner = 'menu_owner';
}
