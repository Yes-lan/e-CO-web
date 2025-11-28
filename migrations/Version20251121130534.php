<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121130534 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE beacon (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, longitude BIGINT NOT NULL, latitude BIGINT NOT NULL, type VARCHAR(255) NOT NULL, is_placed BOOLEAN NOT NULL, placed_at TIME(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIME(0) WITHOUT TIME ZONE DEFAULT NULL, qr VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE beacon_course (beacon_id INT NOT NULL, course_id INT NOT NULL, PRIMARY KEY(beacon_id, course_id))');
        $this->addSql('CREATE INDEX IDX_4EDA6FBCF6AD5578 ON beacon_course (beacon_id)');
        $this->addSql('CREATE INDEX IDX_4EDA6FBC591CC992 ON beacon_course (course_id)');
        $this->addSql('CREATE TABLE boundaries_course (id SERIAL NOT NULL, longitude BIGINT NOT NULL, latitude BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE boundaries_course_course (boundaries_course_id INT NOT NULL, course_id INT NOT NULL, PRIMARY KEY(boundaries_course_id, course_id))');
        $this->addSql('CREATE INDEX IDX_528AFEBD69B69C9F ON boundaries_course_course (boundaries_course_id)');
        $this->addSql('CREATE INDEX IDX_528AFEBD591CC992 ON boundaries_course_course (course_id)');
        $this->addSql('CREATE TABLE course (id SERIAL NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, create_at TIME(0) WITHOUT TIME ZONE NOT NULL, placement_completed_at TIME(0) WITHOUT TIME ZONE NOT NULL, update_at TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE log_session (id SERIAL NOT NULL, type VARCHAR(255) NOT NULL, time TIME(0) WITHOUT TIME ZONE NOT NULL, position BIGINT NOT NULL, latitude BIGINT DEFAULT NULL, longitude BIGINT DEFAULT NULL, additional_data BIGINT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE reset_password_request (id SERIAL NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_7CE748AA76ED395 ON reset_password_request (user_id)');
        $this->addSql('COMMENT ON COLUMN reset_password_request.requested_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN reset_password_request.expires_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE runner (id SERIAL NOT NULL, id_session_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, departure TIME(0) WITHOUT TIME ZONE NOT NULL, arrival TIME(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F92B8B3EC4B56C08 ON runner (id_session_id)');
        $this->addSql('CREATE TABLE session (id SERIAL NOT NULL, id_course_id INT DEFAULT NULL, session_name VARCHAR(255) NOT NULL, nb_runner BIGINT NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_D044D5D4D92975B5 ON session (id_course_id)');
        $this->addSql('CREATE TABLE "user" (id SERIAL NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
        $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
        $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
        $this->addSql('COMMENT ON COLUMN messenger_messages.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.available_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('COMMENT ON COLUMN messenger_messages.delivered_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE OR REPLACE FUNCTION notify_messenger_messages() RETURNS TRIGGER AS $$
            BEGIN
                PERFORM pg_notify(\'messenger_messages\', NEW.queue_name::text);
                RETURN NEW;
            END;
        $$ LANGUAGE plpgsql;');
        $this->addSql('DROP TRIGGER IF EXISTS notify_trigger ON messenger_messages;');
        $this->addSql('CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE ON messenger_messages FOR EACH ROW EXECUTE PROCEDURE notify_messenger_messages();');
        $this->addSql('ALTER TABLE beacon_course ADD CONSTRAINT FK_4EDA6FBCF6AD5578 FOREIGN KEY (beacon_id) REFERENCES beacon (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE beacon_course ADD CONSTRAINT FK_4EDA6FBC591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE boundaries_course_course ADD CONSTRAINT FK_528AFEBD69B69C9F FOREIGN KEY (boundaries_course_id) REFERENCES boundaries_course (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE boundaries_course_course ADD CONSTRAINT FK_528AFEBD591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE runner ADD CONSTRAINT FK_F92B8B3EC4B56C08 FOREIGN KEY (id_session_id) REFERENCES session (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4D92975B5 FOREIGN KEY (id_course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE beacon_course DROP CONSTRAINT FK_4EDA6FBCF6AD5578');
        $this->addSql('ALTER TABLE beacon_course DROP CONSTRAINT FK_4EDA6FBC591CC992');
        $this->addSql('ALTER TABLE boundaries_course_course DROP CONSTRAINT FK_528AFEBD69B69C9F');
        $this->addSql('ALTER TABLE boundaries_course_course DROP CONSTRAINT FK_528AFEBD591CC992');
        $this->addSql('ALTER TABLE reset_password_request DROP CONSTRAINT FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE runner DROP CONSTRAINT FK_F92B8B3EC4B56C08');
        $this->addSql('ALTER TABLE session DROP CONSTRAINT FK_D044D5D4D92975B5');
        $this->addSql('DROP TABLE beacon');
        $this->addSql('DROP TABLE beacon_course');
        $this->addSql('DROP TABLE boundaries_course');
        $this->addSql('DROP TABLE boundaries_course_course');
        $this->addSql('DROP TABLE course');
        $this->addSql('DROP TABLE log_session');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE runner');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
