<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428MapNameTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name_translations JSON column to map (task 135 sous-phase 3e.n)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map DROP COLUMN IF EXISTS name_translations');
    }
}
