<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319SoulboundItems extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 7: Soulbound items - bound_to_player on game_items, bound_to_player_id on player_item';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS bound_to_player BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS bound_to_player_id INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS bound_to_player_id');
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS bound_to_player');
    }
}
