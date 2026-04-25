<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260425StatusEffectNameTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add name_translations JSON column to game_status_effects (task 135 sous-phase 3e.c.statuseffect)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_status_effects ADD COLUMN IF NOT EXISTS name_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_status_effects DROP COLUMN IF EXISTS name_translations');
    }
}
