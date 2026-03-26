<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326ZGuildInfluence extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create guild_influence and influence_log tables (GCC-07)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS guild_influence (
            id SERIAL PRIMARY KEY,
            guild_id INT NOT NULL,
            region_id INT NOT NULL,
            season_id INT NOT NULL,
            points INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT fk_guild_influence_guild FOREIGN KEY (guild_id) REFERENCES guild (id) ON DELETE CASCADE,
            CONSTRAINT fk_guild_influence_region FOREIGN KEY (region_id) REFERENCES region (id) ON DELETE CASCADE,
            CONSTRAINT fk_guild_influence_season FOREIGN KEY (season_id) REFERENCES influence_season (id) ON DELETE CASCADE,
            CONSTRAINT uq_guild_influence_guild_region_season UNIQUE (guild_id, region_id, season_id)
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_guild_influence_ranking ON guild_influence (region_id, season_id, points DESC)');

        $this->addSql('CREATE TABLE IF NOT EXISTS influence_log (
            id SERIAL PRIMARY KEY,
            guild_id INT NOT NULL,
            region_id INT NOT NULL,
            season_id INT NOT NULL,
            player_id INT NOT NULL,
            activity_type VARCHAR(20) NOT NULL,
            points_earned INT NOT NULL,
            details JSON DEFAULT NULL,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT fk_influence_log_guild FOREIGN KEY (guild_id) REFERENCES guild (id) ON DELETE CASCADE,
            CONSTRAINT fk_influence_log_region FOREIGN KEY (region_id) REFERENCES region (id) ON DELETE CASCADE,
            CONSTRAINT fk_influence_log_season FOREIGN KEY (season_id) REFERENCES influence_season (id) ON DELETE CASCADE,
            CONSTRAINT fk_influence_log_player FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_influence_log_guild_season ON influence_log (guild_id, season_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_influence_log_player ON influence_log (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_influence_log_created ON influence_log (created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS influence_log');
        $this->addSql('DROP TABLE IF EXISTS guild_influence');
    }
}
