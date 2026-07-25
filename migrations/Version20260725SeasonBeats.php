<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * NAR-08 : rattachement des GameEvent a une saison comme beats d'arc
 * (season_id + beat + beat_order).
 */
final class Version20260725SeasonBeats extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'NAR-08: season arc beats on game_event (season_id FK + beat + beat_order)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_event ADD COLUMN IF NOT EXISTS season_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_event ADD COLUMN IF NOT EXISTS beat VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE game_event ADD COLUMN IF NOT EXISTS beat_order INT DEFAULT NULL');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_game_event_season') THEN
                    ALTER TABLE game_event
                        ADD CONSTRAINT fk_game_event_season
                        FOREIGN KEY (season_id) REFERENCES influence_season (id)
                        ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
                END IF;
            END $$
            SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_event_season ON game_event (season_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_event DROP CONSTRAINT IF EXISTS fk_game_event_season');
        $this->addSql('DROP INDEX IF EXISTS idx_game_event_season');
        $this->addSql('ALTER TABLE game_event DROP COLUMN IF EXISTS beat_order');
        $this->addSql('ALTER TABLE game_event DROP COLUMN IF EXISTS beat');
        $this->addSql('ALTER TABLE game_event DROP COLUMN IF EXISTS season_id');
    }
}
