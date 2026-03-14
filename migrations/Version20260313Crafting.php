<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313Crafting extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 5: Crafting system - game_recipes table and discovered_recipes column on player';
    }

    public function up(Schema $schema): void
    {
        // Table game_recipes
        $this->addSql('CREATE TABLE game_recipes (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            craft VARCHAR(50) NOT NULL,
            required_level INT NOT NULL DEFAULT 1,
            ingredients JSON NOT NULL,
            result_id INT NOT NULL,
            result_quantity INT NOT NULL DEFAULT 1,
            crafting_time INT NOT NULL DEFAULT 5,
            xp_reward INT NOT NULL DEFAULT 10,
            quality VARCHAR(20) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_game_recipes_result FOREIGN KEY (result_id) REFERENCES game_items (id) ON DELETE RESTRICT
        )');

        $this->addSql('CREATE INDEX idx_game_recipes_craft ON game_recipes (craft)');
        $this->addSql('CREATE INDEX idx_game_recipes_result ON game_recipes (result_id)');

        // Colonne discovered_recipes sur la table player
        $this->addSql('ALTER TABLE player ADD COLUMN discovered_recipes JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS discovered_recipes');
        $this->addSql('DROP TABLE IF EXISTS game_recipes');
    }
}
