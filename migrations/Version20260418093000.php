<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260418093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les colonnes IA et texte CV extrait dans condidature';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE condidature ADD COLUMN IF NOT EXISTS CV_Extracted_Text LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE condidature ADD COLUMN IF NOT EXISTS AI_Score DECIMAL(5,2) DEFAULT NULL');
        $this->addSql('ALTER TABLE condidature ADD COLUMN IF NOT EXISTS AI_Recommendation VARCHAR(32) DEFAULT NULL');
        $this->addSql('ALTER TABLE condidature ADD COLUMN IF NOT EXISTS AI_Summary TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE condidature ADD COLUMN IF NOT EXISTS AI_Last_Analyzed_At DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE condidature DROP COLUMN IF EXISTS AI_Last_Analyzed_At');
        $this->addSql('ALTER TABLE condidature DROP COLUMN IF EXISTS AI_Summary');
        $this->addSql('ALTER TABLE condidature DROP COLUMN IF EXISTS AI_Recommendation');
        $this->addSql('ALTER TABLE condidature DROP COLUMN IF EXISTS AI_Score');
        $this->addSql('ALTER TABLE condidature DROP COLUMN IF EXISTS CV_Extracted_Text');
    }
}
