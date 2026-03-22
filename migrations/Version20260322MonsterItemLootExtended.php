<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322MonsterItemLootExtended extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guaranteed and min_difficulty columns to game_monster_items for extended loot system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monster_items ADD COLUMN IF NOT EXISTS guaranteed BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE game_monster_items ADD COLUMN IF NOT EXISTS min_difficulty INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monster_items DROP COLUMN IF EXISTS guaranteed');
        $this->addSql('ALTER TABLE game_monster_items DROP COLUMN IF EXISTS min_difficulty');
    }
}
