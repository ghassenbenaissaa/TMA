<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240702124910 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE podcast (id INT AUTO_INCREMENT NOT NULL, id_user_id INT NOT NULL, name VARCHAR(255) NOT NULL, INDEX IDX_D7E805BD79F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE podcast ADD CONSTRAINT FK_D7E805BD79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE aventure DROP FOREIGN KEY FK_1E56DE4B79F37AE5');
        $this->addSql('ALTER TABLE aventure ADD CONSTRAINT FK_1E56DE4B79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F9F2F76A8');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F9F2F76A8 FOREIGN KEY (id_aventure_id) REFERENCES aventure (id)');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF79F37AE5');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE podcast DROP FOREIGN KEY FK_D7E805BD79F37AE5');
        $this->addSql('DROP TABLE podcast');
        $this->addSql('ALTER TABLE aventure DROP FOREIGN KEY FK_1E56DE4B79F37AE5');
        $this->addSql('ALTER TABLE aventure ADD CONSTRAINT FK_1E56DE4B79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F9F2F76A8');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F9F2F76A8 FOREIGN KEY (id_aventure_id) REFERENCES aventure (id) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF79F37AE5');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id) ON UPDATE CASCADE ON DELETE CASCADE');
    }
}
