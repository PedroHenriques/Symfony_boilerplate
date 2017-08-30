<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20170711181625 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql('
        CREATE TABLE users
        (id INT UNSIGNED AUTO_INCREMENT NOT NULL, userName VARCHAR(255) NOT NULL,
        email VARCHAR(100) NOT NULL, password VARCHAR(255) NOT NULL, isActive TINYINT(1) NOT NULL,
        roleId INT UNSIGNED NOT NULL, activationHash VARCHAR(255), activationHashGenTs INT UNSIGNED,
        pwResetHash VARCHAR(255), pwResetHashGenTs INT UNSIGNED, created INT UNSIGNED NOT NULL,
        PRIMARY KEY(id), UNIQUE KEY(userName), UNIQUE KEY(email), INDEX(roleId),
        FOREIGN KEY(roleId)
            REFERENCES users_roles(id)
            ON UPDATE CASCADE
        ) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB
        ');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql('DROP TABLE users');
    }
}
