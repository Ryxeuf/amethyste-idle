<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725ZoneExpedition extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_expedition table (pivot PBBG, ZON-13 — time-gated expeditions with loot to claim on return)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_expedition (
                id SERIAL NOT NULL,
                player_id INT NOT NULL,
                zone_id INT NOT NULL,
                duration_key VARCHAR(32) NOT NULL,
                started_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                ends_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                notified_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_expedition_player ON player_expedition (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_expedition_zone ON player_expedition (zone_id)');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_expedition_player') THEN
                    ALTER TABLE player_expedition
                        ADD CONSTRAINT fk_player_expedition_player
                        FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_expedition_zone') THEN
                    ALTER TABLE player_expedition
                        ADD CONSTRAINT fk_player_expedition_zone
                        FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_expedition');
    }
}
