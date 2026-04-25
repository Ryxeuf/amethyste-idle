<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425RaceTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name_translations and description_translations JSON columns to game_races (task 135 sous-phase 3e.f)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_races ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE game_races ADD COLUMN IF NOT EXISTS description_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_races DROP COLUMN IF EXISTS name_translations');
        $this->addSql('ALTER TABLE game_races DROP COLUMN IF EXISTS description_translations');
    }
}
