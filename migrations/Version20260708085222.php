<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260708085222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE classe_user (classe_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY (classe_id, user_id))');
        $this->addSql('CREATE INDEX IDX_9380A3AF8F5EA509 ON classe_user (classe_id)');
        $this->addSql('CREATE INDEX IDX_9380A3AFA76ED395 ON classe_user (user_id)');
        $this->addSql('ALTER TABLE classe_user ADD CONSTRAINT FK_9380A3AF8F5EA509 FOREIGN KEY (classe_id) REFERENCES classe (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE classe_user ADD CONSTRAINT FK_9380A3AFA76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE classe_user DROP CONSTRAINT FK_9380A3AF8F5EA509');
        $this->addSql('ALTER TABLE classe_user DROP CONSTRAINT FK_9380A3AFA76ED395');
        $this->addSql('DROP TABLE classe_user');
    }
}
