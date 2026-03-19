<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260319Achievements extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 12: Achievement system - game_achievements and player_achievements tables';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_achievements (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(255) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            category VARCHAR(50) NOT NULL,
            criteria JSON NOT NULL,
            reward JSON DEFAULT NULL,
            icon VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL
        )');

        $this->addSql('CREATE TABLE IF NOT EXISTS player_achievements (
            id SERIAL PRIMARY KEY,
            player_id INTEGER NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            achievement_id INTEGER NOT NULL REFERENCES game_achievements(id) ON DELETE CASCADE,
            progress INTEGER NOT NULL DEFAULT 0,
            completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP NOT NULL,
            CONSTRAINT player_achievement_unique UNIQUE (player_id, achievement_id)
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_achievements_player ON player_achievements (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_achievements_achievement ON player_achievements (achievement_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_achievements');
        $this->addSql('DROP TABLE IF EXISTS game_achievements');
    }
}
