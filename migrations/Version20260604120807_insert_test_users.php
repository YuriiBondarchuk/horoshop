<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604120807_insert_test_users extends AbstractMigration
{
    private const string TABLE_ROLE_USER = 'user_role';
    private const string TABLE_USERS = 'users';
    private const string TABLE_ROLES = 'roles';
    private const string TEST_ROOT_USER = 'root';
    private const string TEST_USER = 'user';
    private const string ROLE_USER = 'ROLE_USER';
    private const string ROLE_ROOT = 'ROLE_ROOT';

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql(sprintf("INSERT IGNORE INTO %s (name) VALUES ('%s')", self::TABLE_ROLES, self::ROLE_ROOT));
        $this->addSql(sprintf("INSERT IGNORE INTO %s (name) VALUES ('%s')", self::TABLE_ROLES, self::ROLE_USER));

        $this->addSql(
            sprintf(
                "INSERT IGNORE INTO %s (login, phone, pass) VALUES ('%s', '00000000', 'root')",
                self::TABLE_USERS,
                self::TEST_ROOT_USER
            )
        );
        $this->addSql(
            sprintf(
                "INSERT IGNORE INTO %s (login, phone, pass) VALUES ('%s', '11111111', 'user')",
                self::TABLE_USERS,
                self::TEST_USER
            )
        );

        $this->addSql(
            sprintf(
                "
        INSERT IGNORE INTO %s (user_id, role_id)
        VALUES (
            (SELECT id FROM %s WHERE login = '%s' LIMIT 1),
            (SELECT id FROM %s WHERE name = '%s' LIMIT 1)
        )
    ",
                self::TABLE_ROLE_USER,
                self::TABLE_USERS,
                self::TEST_ROOT_USER,
                self::TABLE_ROLES,
                self::ROLE_ROOT
            )
        );

        $this->addSql(
            sprintf(
                "
        INSERT IGNORE INTO %s (user_id, role_id)
        VALUES (
            (SELECT id FROM %s WHERE login = '%s' LIMIT 1),
            (SELECT id FROM %s WHERE name = '%s' LIMIT 1)
        )
    ",
                self::TABLE_ROLE_USER,
                self::TABLE_USERS,
                self::TEST_USER,
                self::TABLE_ROLES,
                self::ROLE_USER
            )
        );
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql(sprintf("DELETE ur FROM %s ur", self::TABLE_ROLE_USER));
        $this->addSql(
            sprintf(
                "DELETE FROM %s WHERE login = '%s' OR login = '%s'",
                self::TABLE_USERS,
                self::TEST_ROOT_USER,
                self::TEST_USER
            )
        );
    }
}
