<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * NAR-12 : marqueur « canon » sur les saisons (curation du journal de monde).
 */
final class Version20260725SeasonCanon extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'NAR-12: is_canon flag on influence_season (world journal curation)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE influence_season ADD COLUMN IF NOT EXISTS is_canon BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE influence_season DROP COLUMN IF EXISTS is_canon');
    }
}
