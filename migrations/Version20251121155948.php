<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251121155948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE beacon_course (beacon_id INT NOT NULL, course_id INT NOT NULL, INDEX IDX_4EDA6FBCF6AD5578 (beacon_id), INDEX IDX_4EDA6FBC591CC992 (course_id), PRIMARY KEY(beacon_id, course_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE establishment (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE beacon_course ADD CONSTRAINT FK_4EDA6FBCF6AD5578 FOREIGN KEY (beacon_id) REFERENCES beacon (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE beacon_course ADD CONSTRAINT FK_4EDA6FBC591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE boundaries_course ADD course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE boundaries_course ADD CONSTRAINT FK_1BEB33BC591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('CREATE INDEX IDX_1BEB33BC591CC992 ON boundaries_course (course_id)');
        $this->addSql('ALTER TABLE course ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE course ADD CONSTRAINT FK_169E6FB9A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('CREATE INDEX IDX_169E6FB9A76ED395 ON course (user_id)');
        $this->addSql('ALTER TABLE log_session ADD runner_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE log_session ADD CONSTRAINT FK_E889ED433C7FB593 FOREIGN KEY (runner_id) REFERENCES runner (id)');
        $this->addSql('CREATE INDEX IDX_E889ED433C7FB593 ON log_session (runner_id)');
        $this->addSql('ALTER TABLE runner ADD session_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE runner ADD CONSTRAINT FK_F92B8B3E613FECDF FOREIGN KEY (session_id) REFERENCES session (id)');
        $this->addSql('CREATE INDEX IDX_F92B8B3E613FECDF ON runner (session_id)');
        $this->addSql('ALTER TABLE session ADD course_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE session ADD CONSTRAINT FK_D044D5D4591CC992 FOREIGN KEY (course_id) REFERENCES course (id)');
        $this->addSql('CREATE INDEX IDX_D044D5D4591CC992 ON session (course_id)');
        $this->addSql('ALTER TABLE user ADD establishment_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6498565851 FOREIGN KEY (establishment_id) REFERENCES establishment (id)');
        $this->addSql('CREATE INDEX IDX_8D93D6498565851 ON user (establishment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D6498565851');
        $this->addSql('ALTER TABLE beacon_course DROP FOREIGN KEY FK_4EDA6FBCF6AD5578');
        $this->addSql('ALTER TABLE beacon_course DROP FOREIGN KEY FK_4EDA6FBC591CC992');
        $this->addSql('DROP TABLE beacon_course');
        $this->addSql('DROP TABLE establishment');
        $this->addSql('ALTER TABLE boundaries_course DROP FOREIGN KEY FK_1BEB33BC591CC992');
        $this->addSql('DROP INDEX IDX_1BEB33BC591CC992 ON boundaries_course');
        $this->addSql('ALTER TABLE boundaries_course DROP course_id');
        $this->addSql('DROP INDEX IDX_8D93D6498565851 ON `user`');
        $this->addSql('ALTER TABLE `user` DROP establishment_id');
        $this->addSql('ALTER TABLE session DROP FOREIGN KEY FK_D044D5D4591CC992');
        $this->addSql('DROP INDEX IDX_D044D5D4591CC992 ON session');
        $this->addSql('ALTER TABLE session DROP course_id');
        $this->addSql('ALTER TABLE course DROP FOREIGN KEY FK_169E6FB9A76ED395');
        $this->addSql('DROP INDEX IDX_169E6FB9A76ED395 ON course');
        $this->addSql('ALTER TABLE course DROP user_id');
        $this->addSql('ALTER TABLE runner DROP FOREIGN KEY FK_F92B8B3E613FECDF');
        $this->addSql('DROP INDEX IDX_F92B8B3E613FECDF ON runner');
        $this->addSql('ALTER TABLE runner DROP session_id');
        $this->addSql('ALTER TABLE log_session DROP FOREIGN KEY FK_E889ED433C7FB593');
        $this->addSql('DROP INDEX IDX_E889ED433C7FB593 ON log_session');
        $this->addSql('ALTER TABLE log_session DROP runner_id');
    }
}
