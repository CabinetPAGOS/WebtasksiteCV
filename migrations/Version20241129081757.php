<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241129081757 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table notification avec les relations vers client et user';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (
            id INT AUTO_INCREMENT NOT NULL,
            client_id VARCHAR(50) NOT NULL,
            user_id VARCHAR(255) NOT NULL,
            message VARCHAR(255) DEFAULT NULL,
            date_creation DATETIME DEFAULT NULL,
            visible TINYINT(1) DEFAULT NULL,
            libelle_webtask VARCHAR(255) DEFAULT NULL,
            titre_webtask VARCHAR(255) DEFAULT NULL,
            code_webtask VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id),
            CONSTRAINT FK_NOTIFICATION_CLIENT FOREIGN KEY (client_id) REFERENCES client (id),
            CONSTRAINT FK_NOTIFICATION_USER FOREIGN KEY (user_id) REFERENCES user (id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification');
    }
}