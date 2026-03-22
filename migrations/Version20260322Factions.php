<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322Factions extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Factions & reputation system - game_factions and player_factions tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_factions (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(64) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            icon VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL
        )');

        $this->addSql('CREATE TABLE IF NOT EXISTS player_factions (
            id SERIAL PRIMARY KEY,
            player_id INTEGER NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            faction_id INTEGER NOT NULL REFERENCES game_factions(id) ON DELETE CASCADE,
            reputation INTEGER NOT NULL DEFAULT 0,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            CONSTRAINT player_faction_unique UNIQUE (player_id, faction_id)
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_factions_player ON player_factions (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_factions_faction ON player_factions (faction_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_factions');
        $this->addSql('DROP TABLE IF EXISTS game_factions');
    }
}
