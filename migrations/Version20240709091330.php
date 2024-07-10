<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240709091330 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image ADD id_podcast_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FDDEBB67C FOREIGN KEY (id_podcast_id) REFERENCES podcast (id)');
        $this->addSql('CREATE INDEX IDX_C53D045FDDEBB67C ON image (id_podcast_id)');
        $this->addSql('ALTER TABLE podcast ADD description VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045FDDEBB67C');
        $this->addSql('DROP INDEX IDX_C53D045FDDEBB67C ON image');
        $this->addSql('ALTER TABLE image DROP id_podcast_id');
        $this->addSql('ALTER TABLE podcast DROP description');
    }
}
