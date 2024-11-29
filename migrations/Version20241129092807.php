<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241129092807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification RENAME INDEX fk_notification_client TO IDX_BF5476CA19EB6921');
        $this->addSql('ALTER TABLE notification RENAME INDEX fk_notification_user TO IDX_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE user CHANGE id id VARCHAR(255) NOT NULL, CHANGE idclient_id idclient_id VARCHAR(50) DEFAULT NULL, CHANGE roles roles JSON NOT NULL, CHANGE webtask_ouverture_contact webtask_ouverture_contact INT DEFAULT NULL');
        $this->addSql('ALTER TABLE webtask CHANGE documents_attaches documents_attaches TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE notification RENAME INDEX idx_bf5476ca19eb6921 TO FK_NOTIFICATION_CLIENT');
        $this->addSql('ALTER TABLE notification RENAME INDEX idx_bf5476caa76ed395 TO FK_NOTIFICATION_USER');
        $this->addSql('ALTER TABLE user CHANGE id id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE idclient_id idclient_id VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE roles roles JSON NOT NULL COLLATE `utf8mb4_bin`, CHANGE webtask_ouverture_contact webtask_ouverture_contact VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE webtask CHANGE documents_attaches documents_attaches TINYINT(1) DEFAULT NULL');
    }
}
