<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240718115923 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE aventure (id INT AUTO_INCREMENT NOT NULL, id_pays_id INT NOT NULL, id_user_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, recommander TINYINT(1) NOT NULL, video VARCHAR(255) DEFAULT NULL, audiance VARCHAR(255) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, INDEX IDX_1E56DE4B7879EB34 (id_pays_id), INDEX IDX_1E56DE4B79F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE continent (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE friend (id INT AUTO_INCREMENT NOT NULL, id_user_id INT DEFAULT NULL, id_2 INT NOT NULL, INDEX IDX_55EEAC6179F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, id_aventure_id INT DEFAULT NULL, id_podcast_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, INDEX IDX_C53D045F9F2F76A8 (id_aventure_id), INDEX IDX_C53D045FDDEBB67C (id_podcast_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pays (id INT AUTO_INCREMENT NOT NULL, id_continent_id INT DEFAULT NULL, nom VARCHAR(255) NOT NULL, INDEX IDX_349F3CAEB1DE3905 (id_continent_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE podcast (id INT AUTO_INCREMENT NOT NULL, id_user_id INT NOT NULL, name VARCHAR(255) NOT NULL, source VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, INDEX IDX_D7E805BD79F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE section (id INT AUTO_INCREMENT NOT NULL, id_user_id INT NOT NULL, type VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, image VARCHAR(255) NOT NULL, emplacement INT DEFAULT NULL, INDEX IDX_2D737AEF79F37AE5 (id_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, age INT NOT NULL, email VARCHAR(255) NOT NULL, mdp VARCHAR(255) NOT NULL, type VARCHAR(255) DEFAULT NULL, theme INT DEFAULT NULL, page_nom VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, system_date DATETIME NOT NULL, facebook VARCHAR(255) DEFAULT NULL, instagram VARCHAR(255) DEFAULT NULL, twitter VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE aventure ADD CONSTRAINT FK_1E56DE4B7879EB34 FOREIGN KEY (id_pays_id) REFERENCES pays (id)');
        $this->addSql('ALTER TABLE aventure ADD CONSTRAINT FK_1E56DE4B79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE friend ADD CONSTRAINT FK_55EEAC6179F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045F9F2F76A8 FOREIGN KEY (id_aventure_id) REFERENCES aventure (id)');
        $this->addSql('ALTER TABLE image ADD CONSTRAINT FK_C53D045FDDEBB67C FOREIGN KEY (id_podcast_id) REFERENCES podcast (id)');
        $this->addSql('ALTER TABLE pays ADD CONSTRAINT FK_349F3CAEB1DE3905 FOREIGN KEY (id_continent_id) REFERENCES continent (id)');
        $this->addSql('ALTER TABLE podcast ADD CONSTRAINT FK_D7E805BD79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE section ADD CONSTRAINT FK_2D737AEF79F37AE5 FOREIGN KEY (id_user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE aventure DROP FOREIGN KEY FK_1E56DE4B7879EB34');
        $this->addSql('ALTER TABLE aventure DROP FOREIGN KEY FK_1E56DE4B79F37AE5');
        $this->addSql('ALTER TABLE friend DROP FOREIGN KEY FK_55EEAC6179F37AE5');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045F9F2F76A8');
        $this->addSql('ALTER TABLE image DROP FOREIGN KEY FK_C53D045FDDEBB67C');
        $this->addSql('ALTER TABLE pays DROP FOREIGN KEY FK_349F3CAEB1DE3905');
        $this->addSql('ALTER TABLE podcast DROP FOREIGN KEY FK_D7E805BD79F37AE5');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE section DROP FOREIGN KEY FK_2D737AEF79F37AE5');
        $this->addSql('DROP TABLE aventure');
        $this->addSql('DROP TABLE continent');
        $this->addSql('DROP TABLE friend');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE pays');
        $this->addSql('DROP TABLE podcast');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE section');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
