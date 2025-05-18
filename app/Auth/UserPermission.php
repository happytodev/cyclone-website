<?php

namespace App\Auth;

use App\Auth\Permission;
use App\Auth\User;
use Tempest\Database\IsDatabaseModel;

final class UserPermission
{
    use IsDatabaseModel;

    public function __construct(
        public User $user,
        public Permission $permission,
    ) {
    }
}
