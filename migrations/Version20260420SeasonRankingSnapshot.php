<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420SeasonRankingSnapshot extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_season_ranking_snapshot table to archive top-N rankings per season (task 132 sous-phase 3)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS player_season_ranking_snapshot (
            id SERIAL NOT NULL,
            season_id INT NOT NULL,
            player_id INT NOT NULL,
            tab VARCHAR(20) NOT NULL,
            rank_position INT NOT NULL,
            player_name VARCHAR(100) NOT NULL,
            total_value BIGINT NOT NULL,
            snapshotted_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_season_tab_rank ON player_season_ranking_snapshot (season_id, tab, rank_position)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ssrs_season_tab ON player_season_ranking_snapshot (season_id, tab)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_ssrs_player ON player_season_ranking_snapshot (player_id)');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_ssrs_season') THEN
                    ALTER TABLE player_season_ranking_snapshot
                        ADD CONSTRAINT fk_ssrs_season
                        FOREIGN KEY (season_id) REFERENCES influence_season (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_ssrs_player') THEN
                    ALTER TABLE player_season_ranking_snapshot
                        ADD CONSTRAINT fk_ssrs_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_season_ranking_snapshot');
    }
}
