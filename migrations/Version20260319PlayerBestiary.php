<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319PlayerBestiary extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 11: Player bestiary - track monster kills per player with milestone tiers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS player_bestiary (
            id SERIAL PRIMARY KEY,
            player_id INTEGER NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            monster_id INTEGER NOT NULL REFERENCES game_monsters(id) ON DELETE CASCADE,
            kill_count INTEGER NOT NULL DEFAULT 0,
            first_encountered_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            first_killed_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT uniq_player_bestiary UNIQUE(player_id, monster_id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_bestiary_player ON player_bestiary(player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_bestiary_monster ON player_bestiary(monster_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_bestiary');
    }
}
