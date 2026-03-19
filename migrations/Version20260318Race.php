<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318Race extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 2: Race system - game_races table and race_id on player';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_races (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(64) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            sprite_sheet VARCHAR(255) DEFAULT NULL,
            stat_modifiers JSON NOT NULL DEFAULT \'{"life":0,"energy":0,"speed":0,"hit":0}\',
            available_at_creation BOOLEAN NOT NULL DEFAULT TRUE,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT CURRENT_TIMESTAMP
        )');

        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS race_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT IF NOT EXISTS fk_player_race FOREIGN KEY (race_id) REFERENCES game_races (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_race ON player (race_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP CONSTRAINT IF EXISTS fk_player_race');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS race_id');
        $this->addSql('DROP TABLE IF EXISTS game_races');
    }
}
