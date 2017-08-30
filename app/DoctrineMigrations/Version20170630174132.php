<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170630174132 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('
        CREATE TABLE users_roles
        (id INT UNSIGNED AUTO_INCREMENT NOT NULL, role VARCHAR(50) NOT NULL,
        PRIMARY KEY(id), UNIQUE KEY(role)
        ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ');

        $this->addSql('
        INSERT INTO users_roles VALUES(null,"ROLE_USER")
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE users_roles');
    }
}
