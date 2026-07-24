<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724ZoneTravel extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player travel fields + player_visited_zone table (pivot PBBG, ZON-06 — real-time travel between zones)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS travel_to_zone_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS travel_arrives_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_travel_to_zone') THEN
                    ALTER TABLE player
                        ADD CONSTRAINT fk_player_travel_to_zone
                        FOREIGN KEY (travel_to_zone_id) REFERENCES zone (id) ON DELETE SET NULL;
                END IF;
            END $$;
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_visited_zone (
                id SERIAL NOT NULL,
                player_id INT NOT NULL,
                zone_id INT NOT NULL,
                first_visited_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_visited_zone ON player_visited_zone (player_id, zone_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_visited_zone_player ON player_visited_zone (player_id)');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_visited_zone_player') THEN
                    ALTER TABLE player_visited_zone
                        ADD CONSTRAINT fk_player_visited_zone_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_visited_zone_zone') THEN
                    ALTER TABLE player_visited_zone
                        ADD CONSTRAINT fk_player_visited_zone_zone
                        FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);

        // Backfill : la zone courante de chaque joueur compte comme decouverte.
        $this->addSql(<<<'SQL'
            INSERT INTO player_visited_zone (player_id, zone_id, first_visited_at)
            SELECT p.id, p.current_zone_id, NOW()
            FROM player p
            WHERE p.current_zone_id IS NOT NULL
            ON CONFLICT (player_id, zone_id) DO NOTHING
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_visited_zone');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT IF EXISTS fk_player_travel_to_zone');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS travel_arrives_at');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS travel_to_zone_id');
    }
}
