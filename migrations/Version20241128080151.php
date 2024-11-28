<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241128080151 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Création de la table Notification';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (
            id INT AUTO_INCREMENT NOT NULL,
            client_id INT NOT NULL,
            user_id INT NOT NULL,
            message VARCHAR(255) DEFAULT NULL,
            date_creation DATETIME DEFAULT NULL,
            visible TINYINT(1) DEFAULT NULL,
            libelle_webtask VARCHAR(255) DEFAULT NULL,
            titre_webtask VARCHAR(255) DEFAULT NULL,
            code_webtask VARCHAR(255) DEFAULT NULL,
            INDEX IDX_1C5EDE44A76ED395 (client_id),
            INDEX IDX_1C5EDE44A76ED395 (user_id),
            PRIMARY KEY(id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_1C5EDE44A76ED395 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_1C5EDE44A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE notification');
    }
}
