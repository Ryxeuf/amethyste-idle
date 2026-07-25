<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725ZoneEvents extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game_event.zone_id + player_zone_event_participation table (pivot PBBG, ZON-15 — announced zone events joinable for energy)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_event ADD COLUMN IF NOT EXISTS zone_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_game_event_zone') THEN
                    ALTER TABLE game_event
                        ADD CONSTRAINT fk_game_event_zone
                        FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE SET NULL;
                END IF;
            END $$;
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_zone_event_participation (
                id SERIAL NOT NULL,
                player_id INT NOT NULL,
                game_event_id INT NOT NULL,
                joined_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                contribution INT NOT NULL DEFAULT 0,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_zone_event ON player_zone_event_participation (player_id, game_event_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_zone_event_participation_event ON player_zone_event_participation (game_event_id)');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_zone_event_participation_player') THEN
                    ALTER TABLE player_zone_event_participation
                        ADD CONSTRAINT fk_zone_event_participation_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_zone_event_participation_event') THEN
                    ALTER TABLE player_zone_event_participation
                        ADD CONSTRAINT fk_zone_event_participation_event
                        FOREIGN KEY (game_event_id) REFERENCES game_event (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_zone_event_participation');
        $this->addSql('ALTER TABLE game_event DROP CONSTRAINT IF EXISTS fk_game_event_zone');
        $this->addSql('ALTER TABLE game_event DROP COLUMN IF EXISTS zone_id');
    }
}
