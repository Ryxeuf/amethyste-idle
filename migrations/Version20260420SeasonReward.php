<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420SeasonReward extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_season_reward table for end-of-season title rewards (task 132 sous-phase 4)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS player_season_reward (
            id SERIAL NOT NULL,
            season_id INT NOT NULL,
            player_id INT NOT NULL,
            tab VARCHAR(20) NOT NULL,
            rank_position INT NOT NULL,
            title_label VARCHAR(120) NOT NULL,
            awarded_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_season_tab_player ON player_season_reward (season_id, tab, player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_psr_season ON player_season_reward (season_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_psr_player ON player_season_reward (player_id)');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_psr_season') THEN
                    ALTER TABLE player_season_reward
                        ADD CONSTRAINT fk_psr_season
                        FOREIGN KEY (season_id) REFERENCES influence_season (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_psr_player') THEN
                    ALTER TABLE player_season_reward
                        ADD CONSTRAINT fk_psr_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_season_reward');
    }
}
