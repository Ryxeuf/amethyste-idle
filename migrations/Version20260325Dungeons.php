<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325Dungeons extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create game_dungeons and dungeon_run tables for instanced dungeon system';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_dungeons (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            map_id INT NOT NULL,
            min_level INT NOT NULL,
            max_players INT NOT NULL DEFAULT 1,
            icon VARCHAR(255) DEFAULT NULL,
            loot_preview JSONB DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT uniq_dungeon_slug UNIQUE (slug),
            CONSTRAINT fk_dungeon_map FOREIGN KEY (map_id) REFERENCES map (id) ON DELETE CASCADE
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_dungeon_map_id ON game_dungeons (map_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS dungeon_run (
            id SERIAL PRIMARY KEY,
            dungeon_id INT NOT NULL,
            player_id INT NOT NULL,
            difficulty VARCHAR(20) NOT NULL,
            started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            CONSTRAINT fk_dungeon_run_dungeon FOREIGN KEY (dungeon_id) REFERENCES game_dungeons (id) ON DELETE CASCADE,
            CONSTRAINT fk_dungeon_run_player FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_dungeon_run_player_dungeon ON dungeon_run (player_id, dungeon_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS dungeon_run');
        $this->addSql('DROP TABLE IF EXISTS game_dungeons');
    }
}
