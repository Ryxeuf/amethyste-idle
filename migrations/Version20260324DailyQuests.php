<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324DailyQuests extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add daily quest fields to game_quests and create player_daily_quest table';
    }

    public function up(Schema $schema): void
    {
        // Add isDaily and dailyPool columns to game_quests
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS is_daily BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS daily_pool VARCHAR(50) DEFAULT NULL');

        // Create player_daily_quest table
        $this->addSql('CREATE TABLE IF NOT EXISTS player_daily_quest (
            id SERIAL PRIMARY KEY,
            player_id INT NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            quest_id INT NOT NULL REFERENCES game_quests(id) ON DELETE CASCADE,
            date DATE NOT NULL,
            tracking JSON DEFAULT \'[]\',
            completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_daily_quest_player ON player_daily_quest (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_daily_quest_date ON player_daily_quest (date)');

        // Unique constraint: one entry per player per quest per day
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'player_daily_quest_unique') THEN
                    ALTER TABLE player_daily_quest ADD CONSTRAINT player_daily_quest_unique UNIQUE (player_id, quest_id, date);
                END IF;
            END $$
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_daily_quest');
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS is_daily');
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS daily_pool');
    }
}
