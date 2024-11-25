<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241120090442 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forum DROP FOREIGN KEY FK_852BBECD19EB6921');
        $this->addSql('DROP INDEX IDX_852BBECD19EB6921 ON forum');
        $this->addSql('ALTER TABLE forum CHANGE client_id client_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA19EB6921 FOREIGN KEY (client_id) REFERENCES client (id)');
        $this->addSql('CREATE INDEX IDX_BF5476CA19EB6921 ON notification (client_id)');
        $this->addSql('ALTER TABLE user CHANGE id id VARCHAR(255) NOT NULL, CHANGE idclient_id idclient_id VARCHAR(50) DEFAULT NULL, CHANGE roles roles JSON NOT NULL, CHANGE webtask_ouverture_contact webtask_ouverture_contact INT DEFAULT NULL');
        $this->addSql('ALTER TABLE webtask CHANGE documents_attaches documents_attaches TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user CHANGE id id VARCHAR(255) NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE idclient_id idclient_id VARCHAR(50) DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE roles roles JSON NOT NULL COLLATE `utf8mb4_bin`, CHANGE webtask_ouverture_contact webtask_ouverture_contact VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE forum CHANGE client_id client_id VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE forum ADD CONSTRAINT FK_852BBECD19EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_852BBECD19EB6921 ON forum (client_id)');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA19EB6921');
        $this->addSql('DROP INDEX IDX_BF5476CA19EB6921 ON notification');
        $this->addSql('ALTER TABLE webtask CHANGE documents_attaches documents_attaches TINYINT(1) DEFAULT NULL');
    }
}
