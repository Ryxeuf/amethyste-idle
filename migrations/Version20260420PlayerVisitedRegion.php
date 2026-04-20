<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420PlayerVisitedRegion extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_visited_region table to track region discovery for fast travel (task 130 sous-phase 5)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS player_visited_region (
            id SERIAL NOT NULL,
            player_id INT NOT NULL,
            region_id INT NOT NULL,
            first_visited_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_visited_region ON player_visited_region (player_id, region_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_visited_region_player ON player_visited_region (player_id)');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_visited_region_player') THEN
                    ALTER TABLE player_visited_region
                        ADD CONSTRAINT fk_player_visited_region_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_visited_region_region') THEN
                    ALTER TABLE player_visited_region
                        ADD CONSTRAINT fk_player_visited_region_region
                        FOREIGN KEY (region_id) REFERENCES region (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_visited_region');
    }
}
