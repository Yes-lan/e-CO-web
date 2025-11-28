<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121085418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE beacon_course (beacon_id INT NOT NULL, course_id INT NOT NULL, PRIMARY KEY(beacon_id, course_id))');
        $this->addSql('CREATE INDEX IDX_4EDA6FBCF6AD5578 ON beacon_course (beacon_id)');
        $this->addSql('CREATE INDEX IDX_4EDA6FBC591CC992 ON beacon_course (course_id)');
        $this->addSql('CREATE TABLE boundaries_course_course (boundaries_course_id INT NOT NULL, course_id INT NOT NULL, PRIMARY KEY(boundaries_course_id, course_id))');
        $this->addSql('CREATE INDEX IDX_528AFEBD69B69C9F ON boundaries_course_course (boundaries_course_id)');
        $this->addSql('CREATE INDEX IDX_528AFEBD591CC992 ON boundaries_course_course (course_id)');
        $this->addSql('ALTER TABLE beacon_course ADD CONSTRAINT FK_4EDA6FBCF6AD5578 FOREIGN KEY (beacon_id) REFERENCES beacon (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE beacon_course ADD CONSTRAINT FK_4EDA6FBC591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE boundaries_course_course ADD CONSTRAINT FK_528AFEBD69B69C9F FOREIGN KEY (boundaries_course_id) REFERENCES boundaries_course (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE boundaries_course_course ADD CONSTRAINT FK_528AFEBD591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE runner ADD id_session_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE runner ADD CONSTRAINT FK_F92B8B3EC4B56C08 FOREIGN KEY (id_session_id) REFERENCES session (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_F92B8B3EC4B56C08 ON runner (id_session_id)');
        $this->addSql('ALTER TABLE session ADD id_course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4D92975B5 FOREIGN KEY (id_course_id) REFERENCES course (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_D044D5D4D92975B5 ON session (id_course_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE beacon_course DROP CONSTRAINT FK_4EDA6FBCF6AD5578');
        $this->addSql('ALTER TABLE beacon_course DROP CONSTRAINT FK_4EDA6FBC591CC992');
        $this->addSql('ALTER TABLE boundaries_course_course DROP CONSTRAINT FK_528AFEBD69B69C9F');
        $this->addSql('ALTER TABLE boundaries_course_course DROP CONSTRAINT FK_528AFEBD591CC992');
        $this->addSql('DROP TABLE beacon_course');
        $this->addSql('DROP TABLE boundaries_course_course');
        $this->addSql('ALTER TABLE runner DROP CONSTRAINT FK_F92B8B3EC4B56C08');
        $this->addSql('DROP INDEX IDX_F92B8B3EC4B56C08');
        $this->addSql('ALTER TABLE runner DROP id_session_id');
        $this->addSql('ALTER TABLE session DROP CONSTRAINT FK_D044D5D4D92975B5');
        $this->addSql('DROP INDEX IDX_D044D5D4D92975B5');
        $this->addSql('ALTER TABLE session DROP id_course_id');
    }
}
