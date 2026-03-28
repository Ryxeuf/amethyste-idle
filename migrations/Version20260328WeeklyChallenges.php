<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328WeeklyChallenges extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create weekly_challenge and guild_challenge_progress tables (GCC-17)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS weekly_challenge (
                id SERIAL PRIMARY KEY,
                season_id INT NOT NULL REFERENCES influence_season(id) ON DELETE CASCADE,
                title VARCHAR(100) NOT NULL,
                description TEXT NOT NULL,
                activity_type VARCHAR(20) NOT NULL,
                criteria JSON NOT NULL,
                bonus_points INT NOT NULL,
                week_number INT NOT NULL,
                starts_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                ends_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_weekly_challenge_season_week ON weekly_challenge (season_id, week_number)
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS guild_challenge_progress (
                id SERIAL PRIMARY KEY,
                guild_id INT NOT NULL REFERENCES guild(id) ON DELETE CASCADE,
                challenge_id INT NOT NULL REFERENCES weekly_challenge(id) ON DELETE CASCADE,
                progress INT NOT NULL DEFAULT 0,
                completed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                CONSTRAINT uq_guild_challenge_progress UNIQUE (guild_id, challenge_id)
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS guild_challenge_progress');
        $this->addSql('DROP TABLE IF EXISTS weekly_challenge');
    }
}
