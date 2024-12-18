<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241218204036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE discipline_students DROP FOREIGN KEY FK_C6DE580DA5522701');
        $this->addSql('ALTER TABLE discipline_students DROP FOREIGN KEY FK_C6DE580DA76ED395');
        $this->addSql('DROP TABLE discipline_students');
        $this->addSql('ALTER TABLE discipline ADD teacher_id INT NOT NULL');
        $this->addSql('ALTER TABLE discipline ADD CONSTRAINT FK_75BEEE3F41807E1D FOREIGN KEY (teacher_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_75BEEE3F41807E1D ON discipline (teacher_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE discipline_students (discipline_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_C6DE580DA5522701 (discipline_id), INDEX IDX_C6DE580DA76ED395 (user_id), PRIMARY KEY(discipline_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE discipline_students ADD CONSTRAINT FK_C6DE580DA5522701 FOREIGN KEY (discipline_id) REFERENCES discipline (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discipline_students ADD CONSTRAINT FK_C6DE580DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE discipline DROP FOREIGN KEY FK_75BEEE3F41807E1D');
        $this->addSql('DROP INDEX IDX_75BEEE3F41807E1D ON discipline');
        $this->addSql('ALTER TABLE discipline DROP teacher_id');
    }
}
