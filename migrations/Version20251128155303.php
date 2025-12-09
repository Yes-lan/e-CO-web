<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251128155303 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE beacon (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, longitude DOUBLE PRECISION NOT NULL, latitude DOUBLE PRECISION NOT NULL, type VARCHAR(255) NOT NULL, is_placed TINYINT(1) NOT NULL, placed_at DATETIME DEFAULT NULL, created_at DATETIME DEFAULT NULL, qr VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE beacon_course (beacon_id INT NOT NULL, course_id INT NOT NULL, INDEX IDX_4EDA6FBCF6AD5578 (beacon_id), INDEX IDX_4EDA6FBC591CC992 (course_id), PRIMARY KEY(beacon_id, course_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE course (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, create_at DATETIME NOT NULL, placement_completed_at DATETIME NOT NULL, update_at DATETIME NOT NULL, same_start_finish TINYINT(1) DEFAULT 0 NOT NULL, INDEX IDX_169E6FB9A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE establishment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE log_session (id INT AUTO_INCREMENT NOT NULL, runner_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, time DATETIME NOT NULL, latitude DOUBLE PRECISION DEFAULT NULL, longitude DOUBLE PRECISION DEFAULT NULL, additional_data LONGTEXT DEFAULT NULL, INDEX IDX_E889ED433C7FB593 (runner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE runner (id INT AUTO_INCREMENT NOT NULL, session_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, departure DATETIME NOT NULL, arrival DATETIME NOT NULL, INDEX IDX_F92B8B3E613FECDF (session_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE session (id INT AUTO_INCREMENT NOT NULL, course_id INT DEFAULT NULL, session_name VARCHAR(255) NOT NULL, nb_runner INT NOT NULL, session_start DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', session_end DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_D044D5D4591CC992 (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, establishment_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, INDEX IDX_8D93D6498565851 (establishment_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE beacon_course ADD CONSTRAINT FK_4EDA6FBCF6AD5578 FOREIGN KEY (beacon_id) REFERENCES beacon (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beacon_course ADD CONSTRAINT FK_4EDA6FBC591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB9A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE log_session ADD CONSTRAINT FK_E889ED433C7FB593 FOREIGN KEY (runner_id) REFERENCES runner (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE runner ADD CONSTRAINT FK_F92B8B3E613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('ALTER TABLE `user` ADD CONSTRAINT FK_8D93D6498565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE TABLE language (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(5) NOT NULL, displayed_text VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO language (code, displayed_text) VALUES ('fr', 'Français')");
        $this->addSql("INSERT INTO language (code, displayed_text) VALUES ('en', 'English')");
        $this->addSql("INSERT INTO language (code, displayed_text) VALUES ('eu', 'Euskara')");

        // Insert establishment
        $this->addSql("INSERT INTO establishment (name) VALUES ('Test School')");

        // Insert admin user
        $this->addSql("INSERT INTO user (establishment_id, roles, email, password, first_name, last_name) VALUES (1, '[\"ROLE_ADMIN\"]', 'test@test.fr', '\$2y\$13\$e4uQ/Bf4yixU/tDxoV83hOk33Kgd5chJwPNStsruOzv2qHb02Bdl6', 'Sandra', 'Doe')");

        // Insert Lilou Doe
        $this->addSql("INSERT INTO user (establishment_id, roles, email, password, first_name, last_name) VALUES (1, '[\"ROLE_USER\"]', 'lilou@doe.com', '\$2y\$12\$dymB4uwai3dj2F3xj74V1.jFQT4XVCNOwwOmDGGwrN21kdVG8m0K6', 'Lilou', 'Doe')");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE beacon_course DROP FOREIGN KEY FK_4EDA6FBCF6AD5578');
        $this->addSql('ALTER TABLE beacon_course DROP FOREIGN KEY FK_4EDA6FBC591CC992');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB9A76ED395');
        $this->addSql('ALTER TABLE log_session DROP FOREIGN KEY FK_E889ED433C7FB593');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE runner DROP FOREIGN KEY FK_F92B8B3E613FECDF');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4591CC992');
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6498565851');
        $this->addSql('DROP TABLE beacon');
        $this->addSql('DROP TABLE beacon_course');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE establishment');
        $this->addSql('DROP TABLE language');
        $this->addSql('DROP TABLE log_session');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE runner');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
