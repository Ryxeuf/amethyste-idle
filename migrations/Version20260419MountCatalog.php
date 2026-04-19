<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260419MountCatalog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game_mounts table for mount catalog (task 130 sous-phase 1)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_mounts (
            id SERIAL NOT NULL,
            slug VARCHAR(64) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            sprite_sheet VARCHAR(255) DEFAULT NULL,
            icon_path VARCHAR(255) DEFAULT NULL,
            speed_bonus INT DEFAULT 50 NOT NULL,
            obtention_type VARCHAR(32) DEFAULT \'purchase\' NOT NULL,
            gil_cost INT DEFAULT NULL,
            required_level INT DEFAULT 1 NOT NULL,
            enabled BOOLEAN DEFAULT TRUE NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_game_mounts_slug ON game_mounts (slug)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_mounts_enabled ON game_mounts (enabled)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS game_mounts');
    }
}
