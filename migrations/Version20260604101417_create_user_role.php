<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604101417_create_user_role extends AbstractMigration
{
    private const string TABLE_NAME = 'user_role';

    #[\Override]
    public function up(Schema $schema): void
    {
        $this->addSql(
            sprintf(
                'CREATE TABLE %s(
                    id SERIAL NOT NULL PRIMARY KEY,
                    user_id BIGINT UNSIGNED NOT NULL,
                    role_id BIGINT UNSIGNED NOT NULL,

                    FOREIGN KEY(user_id) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE,
                    FOREIGN KEY(role_id) REFERENCES roles(id) ON UPDATE CASCADE ON DELETE CASCADE
                )',
                self::TABLE_NAME
            )
        );

        $this->addSql(
            sprintf('CREATE UNIQUE INDEX %s__user_role ON %s(user_id,role_id)', self::TABLE_NAME, self::TABLE_NAME)
        );
    }

    #[\Override]
    public function down(Schema $schema): void
    {
        $this->addSql(sprintf('DROP TABLE %s', self::TABLE_NAME));
    }
}
