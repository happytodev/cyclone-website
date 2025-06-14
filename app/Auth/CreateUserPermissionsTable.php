<?php

namespace App\Auth;

use App\Auth\Permission;
use Tempest\Database\DatabaseMigration;
use Tempest\Database\QueryStatements\CreateTableStatement;
use Tempest\Database\QueryStatements\DropTableStatement;

final class CreateUserPermissionsTable implements DatabaseMigration
{
    private(set) string $name = '0000-00-02_create_user_permissions_table';

    public function up(): CreateTableStatement
    {
        return new CreateTableStatement('user_permissions')
            ->primary()
            ->belongsTo('user_permissions.user_id', 'users.id')
            ->belongsTo('user_permissions.permission_id', 'permissions.id');
    }

    public function down(): DropTableStatement
    {
        return DropTableStatement::forModel(Permission::class);
    }
}
