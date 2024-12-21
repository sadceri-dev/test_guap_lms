<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20241221082011 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE submission (id INT AUTO_INCREMENT NOT NULL, student_id INT NOT NULL, discipline_id INT NOT NULL, filename VARCHAR(255) NOT NULL, submitted_at DATETIME NOT NULL, INDEX IDX_DB055AF3CB944F1A (student_id), INDEX IDX_DB055AF3A5522701 (discipline_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3CB944F1A FOREIGN KEY (student_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE submission ADD CONSTRAINT FK_DB055AF3A5522701 FOREIGN KEY (discipline_id) REFERENCES discipline (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF3CB944F1A');
        $this->addSql('ALTER TABLE submission DROP FOREIGN KEY FK_DB055AF3A5522701');
        $this->addSql('DROP TABLE submission');
    }
}
