<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323FactionRewards extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add faction_id to monsters, create faction_rewards table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS faction_id INTEGER DEFAULT NULL');
        $this->addSql('DO $$ BEGIN IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_monsters_faction\') THEN ALTER TABLE game_monsters ADD CONSTRAINT fk_monsters_faction FOREIGN KEY (faction_id) REFERENCES game_factions (id) ON DELETE SET NULL; END IF; END $$');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_monsters_faction ON game_monsters (faction_id)');

        $this->addSql('CREATE TABLE IF NOT EXISTS game_faction_rewards (
            id SERIAL PRIMARY KEY,
            faction_id INTEGER NOT NULL,
            required_tier VARCHAR(32) NOT NULL,
            reward_type VARCHAR(64) NOT NULL,
            reward_data JSON NOT NULL DEFAULT \'{}\'::json,
            label VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT fk_faction_rewards_faction FOREIGN KEY (faction_id) REFERENCES game_factions (id) ON DELETE CASCADE
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_faction_rewards_faction ON game_faction_rewards (faction_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_faction_rewards_tier ON game_faction_rewards (required_tier)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS game_faction_rewards');
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS faction_id');
    }
}
