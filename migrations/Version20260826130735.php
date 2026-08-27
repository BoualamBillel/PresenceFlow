<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260826130735 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX uniq_emargement_etudiant_session ON emargement (etudiant_id, session_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_session_qr_code_token ON session_cours (qr_code_token)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_emargement_etudiant_session');
        $this->addSql('DROP INDEX uniq_session_qr_code_token');
    }
}
