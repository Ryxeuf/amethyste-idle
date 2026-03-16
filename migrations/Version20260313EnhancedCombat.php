<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313EnhancedCombat extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Phase 3: Enhanced Combat System - status effects, spell enhancements, monster AI/boss, elemental synergies, materia fusion, cooldowns';
    }

    public function up(Schema $schema): void
    {
        // Create game_status_effects table
        $this->addSql('CREATE TABLE IF NOT EXISTS game_status_effects (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            duration INT NOT NULL,
            damage_per_turn INT DEFAULT NULL,
            heal_per_turn INT DEFAULT NULL,
            stat_modifier JSON DEFAULT NULL,
            chance INT NOT NULL DEFAULT 100,
            element VARCHAR(25) NOT NULL DEFAULT \'none\',
            icon VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');

        // Create fight_status_effect table
        $this->addSql('CREATE TABLE IF NOT EXISTS fight_status_effect (
            id SERIAL PRIMARY KEY,
            fight_id INT NOT NULL REFERENCES fight(id),
            target_type VARCHAR(20) NOT NULL,
            target_id INT NOT NULL,
            status_effect_id INT NOT NULL REFERENCES game_status_effects(id),
            remaining_turns INT NOT NULL,
            applied_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_fight_status_effect_fight ON fight_status_effect (fight_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_fight_status_effect_status ON fight_status_effect (status_effect_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_fight_status_effect_target ON fight_status_effect (target_type, target_id)');

        // Spell enhancements
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS cooldown INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS energy_cost INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS status_effect_slug VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game_spells ADD COLUMN IF NOT EXISTS aoe_targets INT NOT NULL DEFAULT 1');

        // Monster AI & Boss
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS ai_pattern JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS elemental_resistances JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS is_boss BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS boss_phases JSON DEFAULT NULL');

        // Fight: elemental synergy tracking + cooldowns
        $this->addSql('ALTER TABLE fight ADD COLUMN IF NOT EXISTS last_element_used VARCHAR(25) DEFAULT NULL');
        $this->addSql('ALTER TABLE fight ADD COLUMN IF NOT EXISTS cooldowns JSON DEFAULT NULL');

        // PlayerItem: materia experience
        $this->addSql('ALTER TABLE player_item ADD COLUMN IF NOT EXISTS experience INT NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        // PlayerItem
        $this->addSql('ALTER TABLE player_item DROP COLUMN experience');

        // Fight
        $this->addSql('ALTER TABLE fight DROP COLUMN last_element_used');
        $this->addSql('ALTER TABLE fight DROP COLUMN cooldowns');

        // Monster
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN ai_pattern');
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN elemental_resistances');
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN is_boss');
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN boss_phases');

        // Spell
        $this->addSql('ALTER TABLE game_spells DROP COLUMN cooldown');
        $this->addSql('ALTER TABLE game_spells DROP COLUMN energy_cost');
        $this->addSql('ALTER TABLE game_spells DROP COLUMN status_effect_slug');
        $this->addSql('ALTER TABLE game_spells DROP COLUMN aoe_targets');

        // Drop tables
        $this->addSql('DROP TABLE fight_status_effect');
        $this->addSql('DROP TABLE game_status_effects');
    }
}
