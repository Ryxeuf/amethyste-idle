<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260322FactionRewards extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Faction rewards table + faction fields on monsters for reputation gains';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_faction_rewards (
            id SERIAL PRIMARY KEY,
            faction_id INTEGER NOT NULL REFERENCES game_factions(id) ON DELETE CASCADE,
            required_tier VARCHAR(32) NOT NULL,
            reward_type VARCHAR(64) NOT NULL,
            reward_data JSON DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_faction_rewards_faction ON game_faction_rewards (faction_id)');

        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS faction_id INTEGER DEFAULT NULL REFERENCES game_factions(id)');
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS faction_reputation_reward INTEGER DEFAULT NULL');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_monsters_faction ON game_monsters (faction_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS game_faction_rewards');
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS faction_id');
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS faction_reputation_reward');
    }
}
