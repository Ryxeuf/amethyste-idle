<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326Enchantments extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enchantements temporaires — definitions et instances actives sur les items equipes';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_enchantment_definitions (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(255) NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            description TEXT DEFAULT NULL,
            element VARCHAR(25) NOT NULL DEFAULT \'none\',
            stat_bonuses JSON NOT NULL DEFAULT \'[]\',
            duration INTEGER NOT NULL,
            ingredients JSON NOT NULL DEFAULT \'[]\',
            required_level INTEGER NOT NULL DEFAULT 1,
            cost INTEGER NOT NULL DEFAULT 0,
            icon VARCHAR(100) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');

        $this->addSql('CREATE TABLE IF NOT EXISTS enchantments (
            id SERIAL PRIMARY KEY,
            player_item_id INTEGER NOT NULL REFERENCES player_item(id) ON DELETE CASCADE,
            enchantment_definition_id INTEGER NOT NULL REFERENCES game_enchantment_definitions(id),
            applied_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            expires_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_enchantment_player_item ON enchantments(player_item_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS IDX_enchantment_expires ON enchantments(expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS enchantments');
        $this->addSql('DROP TABLE IF EXISTS game_enchantment_definitions');
    }
}
