<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version2026060408500_create_users extends AbstractMigration
{
    private const string TABLE_NAME = 'users';

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'CREATE TABLE %s(
                    id SERIAL NOT NULL PRIMARY KEY,
                    login VARCHAR(8) NOT NULL,
                    phone VARCHAR(8) NOT NULL,
                    pass VARCHAR(8) NOT NULL
                )',
                self::TABLE_NAME
            )
        );

        $this->addSql(sprintf('CREATE UNIQUE INDEX user__login_pass ON %s(login,pass)', self::TABLE_NAME));
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql(sprintf('DROP TABLE %s', self::TABLE_NAME));
    }
}
