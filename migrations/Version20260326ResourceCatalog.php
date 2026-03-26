<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326ResourceCatalog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Herbier & catalogue minier — track resource collection per player with milestone tiers';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS player_resource_catalog (
            id SERIAL PRIMARY KEY,
            player_id INTEGER NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            item_id INTEGER NOT NULL REFERENCES game_items(id) ON DELETE CASCADE,
            collect_count INTEGER NOT NULL DEFAULT 0,
            first_collected_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT uniq_player_resource_catalog UNIQUE(player_id, item_id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_resource_catalog_player ON player_resource_catalog(player_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_resource_catalog_item ON player_resource_catalog(item_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_resource_catalog');
    }
}
