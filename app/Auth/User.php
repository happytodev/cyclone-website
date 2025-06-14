<?php

namespace App\Auth;

use SensitiveParameter;
use Tempest\Auth\CanAuthenticate;
use Tempest\Auth\CanAuthorize;
use App\Auth\Permission;
use App\Auth\UserPermission;
use Tempest\Database\IsDatabaseModel;
use UnitEnum;

use function Tempest\Support\arr;

final class User implements CanAuthenticate, CanAuthorize
{
    use IsDatabaseModel;

    public string $password;

    public function __construct(
        public string $name,
        public string $email,
        /** @var \App\Auth\UserPermission[] $userPermissions */
        public array $userPermissions = [],
    ) {
    }

    /**
     * @param string $password The raw password, which will be encrypted as soon as it is set
     */
    public function setPassword(
        #[SensitiveParameter]
        string $password,
    ): self {
        $this->password = password_hash($password, PASSWORD_BCRYPT);

        return $this;
    }

    public function grantPermission(string|UnitEnum|Permission $permission): self
    {
        $permission = $this->resolvePermission($permission);

        new UserPermission(
            user: $this,
            permission: $permission,
        )->save();

        return $this->load('userPermissions.permission');
    }

    public function revokePermission(string|UnitEnum|Permission $permission): self
    {
        $this->getPermission($permission)?->delete();

        return $this->load('userPermissions.permission');
    }

    public function hasPermission(string|UnitEnum|Permission $permission): bool
    {
        return $this->getPermission($permission) !== null;
    }

    public function getPermission(string|UnitEnum|Permission $permission): ?UserPermission
    {
        return arr($this->userPermissions)
            ->first(fn (UserPermission $userPermission) => $userPermission->permission->matches($permission));
    }

    private function resolvePermission(string|UnitEnum|Permission $permission): Permission
    {
        if ($permission instanceof Permission) {
            return $permission;
        }

        $name = match (true) {
            is_string($permission) => $permission,
            $permission instanceof \BackedEnum => $permission->value,
            $permission instanceof UnitEnum => $permission->name,
        };

        $permission = Permission::select()->whereField('name', $name)->first();

        return $permission ?? new Permission($name)->save();
    }
}
