<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422PnjNameTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name_translations JSON column to pnj (task 135 sous-phase 3e.d)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pnj ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE pnj DROP COLUMN IF EXISTS name_translations');
    }
}
