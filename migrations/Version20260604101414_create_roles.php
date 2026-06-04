<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604101414_create_roles extends AbstractMigration
{
    private const string TABLE_NAME = 'roles';

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'CREATE TABLE %s(
                    id SERIAL NOT NULL PRIMARY KEY,
                    name VARCHAR(32) NOT NULL
                )',
                self::TABLE_NAME
            )
        );

        $this->addSql(sprintf('CREATE UNIQUE INDEX role__name ON %s(name)', self::TABLE_NAME));
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql(sprintf('DROP TABLE %s', self::TABLE_NAME));
    }
}
