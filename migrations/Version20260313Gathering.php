<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313Gathering extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 4: Gathering system - add durability and gathering_type columns';
    }

    public function up(Schema $schema): void
    {
        // Add durability column to player_item (if not already present from HarvestAndCraft migration)
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS durability INT DEFAULT NULL');

        // Add max_durability column to game_items (if not already present)
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS max_durability INT DEFAULT NULL');

        // Add gathering_type column to game_items (values: fishing_rod, skinning_knife, fish, leather, etc.)
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS gathering_type VARCHAR(50) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS durability');
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS max_durability');
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS gathering_type');
    }
}
