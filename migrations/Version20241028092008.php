<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241028092008 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create forum table with client relation';
    }

    public function up(Schema $schema): void
    {
        // Création de la table forum
        $this->addSql('CREATE TABLE forum (id INT AUTO_INCREMENT NOT NULL, client_id VARCHAR(50) NOT NULL, content LONGTEXT NOT NULL, date DATETIME NOT NULL, INDEX IDX_852BBECD19EB6921 (client_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Ajout de la contrainte de clé étrangère sur client_id
        $this->addSql('ALTER TABLE forum ADD CONSTRAINT FK_852BBECD19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
    }

    public function down(Schema $schema): void
    {
        // Suppression de la table forum et de la contrainte de clé étrangère
        $this->addSql('ALTER TABLE forum DROP FOREIGN KEY FK_852BBECD19EB6921');
        $this->addSql('DROP TABLE forum');
    }
}
