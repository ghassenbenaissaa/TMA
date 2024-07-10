<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240710114847 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aventure DROP FOREIGN KEY FK_1E56DE4B79F37AE5');
        $this->addSql('ALTER TABLE aventure ADD audiance VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE aventure ADD CONSTRAINT FK_1E56DE4B79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045FDDEBB67C');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F9F2F76A8');
        $this->addSql('ALTER TABLE image CHANGE id_aventure_id id_aventure_id INT NOT NULL');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FDDEBB67C FOREIGN KEY (id_podcast_id) REFERENCES podcast (id)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F9F2F76A8 FOREIGN KEY (id_aventure_id) REFERENCES aventure (id)');
        $this->addSql('ALTER TABLE podcast DROP FOREIGN KEY FK_D7E805BD79F37AE5');
        $this->addSql('ALTER TABLE podcast ADD CONSTRAINT FK_D7E805BD79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF79F37AE5');
        $this->addSql('ALTER TABLE section CHANGE image image VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aventure DROP FOREIGN KEY FK_1E56DE4B79F37AE5');
        $this->addSql('ALTER TABLE aventure DROP audiance');
        $this->addSql('ALTER TABLE aventure ADD CONSTRAINT FK_1E56DE4B79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F9F2F76A8');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045FDDEBB67C');
        $this->addSql('ALTER TABLE image CHANGE id_aventure_id id_aventure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F9F2F76A8 FOREIGN KEY (id_aventure_id) REFERENCES aventure (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FDDEBB67C FOREIGN KEY (id_podcast_id) REFERENCES podcast (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE podcast DROP FOREIGN KEY FK_D7E805BD79F37AE5');
        $this->addSql('ALTER TABLE podcast ADD CONSTRAINT FK_D7E805BD79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF79F37AE5');
        $this->addSql('ALTER TABLE section CHANGE image image VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
    }
}
