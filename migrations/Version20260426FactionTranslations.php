<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260426FactionTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name_translations and description_translations JSON columns to game_factions (task 135 sous-phase 3e.h)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_factions ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE game_factions ADD COLUMN IF NOT EXISTS description_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_factions DROP COLUMN IF EXISTS name_translations');
        $this->addSql('ALTER TABLE game_factions DROP COLUMN IF EXISTS description_translations');
    }
}
