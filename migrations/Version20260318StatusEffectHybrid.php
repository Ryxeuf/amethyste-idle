<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260318StatusEffectHybrid extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 4: Side effects enrichis — category, frequency, realTimeDuration, lastTickTurn, player_status_effects';
    }

    public function up(Schema $schema): void
    {
        // StatusEffect: add category, frequency, real_time_duration
        $this->addSql('ALTER TABLE game_status_effects ADD COLUMN IF NOT EXISTS category VARCHAR(20) DEFAULT NULL');
        $this->addSql('ALTER TABLE game_status_effects ADD COLUMN IF NOT EXISTS frequency INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_status_effects ADD COLUMN IF NOT EXISTS real_time_duration INT DEFAULT NULL');

        // FightStatusEffect: add last_tick_turn
        $this->addSql('ALTER TABLE fight_status_effect ADD COLUMN IF NOT EXISTS last_tick_turn INT DEFAULT NULL');

        // PlayerStatusEffect: persistent effects out of combat
        $this->addSql('CREATE TABLE IF NOT EXISTS player_status_effects (
            id SERIAL PRIMARY KEY,
            player_id INT NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            status_effect_id INT NOT NULL REFERENCES game_status_effects(id),
            applied_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_player_status_effect_player ON player_status_effects (player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_player_status_effect_expires ON player_status_effects (expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_status_effects');
        $this->addSql('ALTER TABLE fight_status_effect DROP COLUMN IF EXISTS last_tick_turn');
        $this->addSql('ALTER TABLE game_status_effects DROP COLUMN IF EXISTS category');
        $this->addSql('ALTER TABLE game_status_effects DROP COLUMN IF EXISTS frequency');
        $this->addSql('ALTER TABLE game_status_effects DROP COLUMN IF EXISTS real_time_duration');
    }
}
