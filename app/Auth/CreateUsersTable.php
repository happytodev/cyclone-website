<?php

namespace App\Auth;

use App\Auth\User;
use Tempest\Database\DatabaseMigration;
use Tempest\Database\QueryStatements\CreateTableStatement;
use Tempest\Database\QueryStatements\DropTableStatement;

final class CreateUsersTable implements DatabaseMigration
{
    private(set) string $name = '0000-00-00_create_users_table';

    public function up(): CreateTableStatement
    {
        return new CreateTableStatement('users')
            ->primary()
            ->varchar('name')
            ->varchar('email')
            ->datetime('emailValidatedAt', nullable: true)
            ->text('password');
    }

    public function down(): DropTableStatement
    {
        return DropTableStatement::forModel(User::class);
    }
}
