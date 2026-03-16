<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313HarvestAndCraft extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 4 & 5: Harvest system (tool fields, durability, respawn) + Craft system (craft_recipes table)';
    }

    public function up(Schema $schema): void
    {
        // Item: add tool fields
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS tool_type VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS tool_tier INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS durability INT DEFAULT NULL');

        // PlayerItem: add current_durability
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS current_durability INT DEFAULT NULL');

        // ObjectLayer: add respawn_delay and required_tool_type
        $this->addSql('ALTER TABLE object_layer ADD COLUMN IF NOT EXISTS respawn_delay INT DEFAULT NULL');
        $this->addSql('ALTER TABLE object_layer ADD COLUMN IF NOT EXISTS required_tool_type VARCHAR(50) DEFAULT NULL');

        // CraftRecipe table
        $this->addSql('CREATE TABLE IF NOT EXISTS game_craft_recipes (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            profession VARCHAR(50) NOT NULL,
            ingredients JSON NOT NULL,
            result_item_slug VARCHAR(255) NOT NULL,
            result_quantity INT NOT NULL DEFAULT 1,
            required_skill_slug VARCHAR(255) DEFAULT NULL,
            required_level INT NOT NULL DEFAULT 1,
            craft_time INT NOT NULL DEFAULT 5,
            experience_gain INT NOT NULL DEFAULT 1,
            is_discoverable BOOLEAN NOT NULL DEFAULT FALSE,
            is_discovered BOOLEAN NOT NULL DEFAULT FALSE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS tool_type');
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS tool_tier');
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS durability');
        $this->addSql('ALTER TABLE player_item DROP COLUMN IF EXISTS current_durability');
        $this->addSql('ALTER TABLE object_layer DROP COLUMN IF EXISTS respawn_delay');
        $this->addSql('ALTER TABLE object_layer DROP COLUMN IF EXISTS required_tool_type');
        $this->addSql('DROP TABLE IF EXISTS game_craft_recipes');
    }
}
