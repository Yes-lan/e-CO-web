<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121084204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE boundariescourse_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE logsession_id_seq CASCADE');
        $this->addSql('CREATE TABLE boundaries_course (id SERIAL NOT NULL, longitude BIGINT NOT NULL, latitude BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE log_session (id SERIAL NOT NULL, type VARCHAR(255) NOT NULL, time TIME(0) WITHOUT TIME ZONE NOT NULL, position BIGINT NOT NULL, latitude BIGINT DEFAULT NULL, longitude BIGINT DEFAULT NULL, additional_data BIGINT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE reset_password_request (id SERIAL NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE boundariescourse DROP CONSTRAINT fk_833cbd99591cc992');
        $this->addSql('DROP TABLE logsession');
        $this->addSql('DROP TABLE boundariescourse');
        $this->addSql('ALTER TABLE beacon DROP CONSTRAINT fk_244829e7591cc992');
        $this->addSql('DROP INDEX idx_244829e7591cc992');
        $this->addSql('ALTER TABLE beacon DROP course_id');
        $this->addSql('ALTER TABLE runner DROP CONSTRAINT fk_f92b8b3e613fecdf');
        $this->addSql('DROP INDEX idx_f92b8b3e613fecdf');
        $this->addSql('ALTER TABLE runner DROP session_id');
        $this->addSql('ALTER TABLE session DROP CONSTRAINT fk_d044d5d4591cc992');
        $this->addSql('DROP INDEX idx_d044d5d4591cc992');
        $this->addSql('ALTER TABLE session DROP course_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE boundariescourse_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE logsession_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE logsession (id SERIAL NOT NULL, type VARCHAR(255) NOT NULL, "time" TIME(0) WITHOUT TIME ZONE NOT NULL, "position" BIGINT NOT NULL, latitude BIGINT DEFAULT NULL, longitude BIGINT DEFAULT NULL, additional_data BIGINT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE boundariescourse (id SERIAL NOT NULL, course_id INT DEFAULT NULL, longitude BIGINT NOT NULL, latitude BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX idx_833cbd99591cc992 ON boundariescourse (course_id)');
        $this->addSql('ALTER TABLE boundariescourse ADD CONSTRAINT fk_833cbd99591cc992 FOREIGN KEY (course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reset_password_request DROP CONSTRAINT FK_7CE748AA76ED395');
        $this->addSql('DROP TABLE boundaries_course');
        $this->addSql('DROP TABLE log_session');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('ALTER TABLE runner ADD session_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE runner ADD CONSTRAINT fk_f92b8b3e613fecdf FOREIGN KEY (session_id) REFERENCES session (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_f92b8b3e613fecdf ON runner (session_id)');
        $this->addSql('ALTER TABLE beacon ADD course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE beacon ADD CONSTRAINT fk_244829e7591cc992 FOREIGN KEY (course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_244829e7591cc992 ON beacon (course_id)');
        $this->addSql('ALTER TABLE session ADD course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT fk_d044d5d4591cc992 FOREIGN KEY (course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d044d5d4591cc992 ON session (course_id)');
    }
}
