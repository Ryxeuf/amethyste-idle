<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326Enchantment extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create enchantment table for temporary item buffs';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS enchantment (
            id SERIAL PRIMARY KEY,
            player_item_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            stat VARCHAR(30) NOT NULL,
            value INT NOT NULL,
            element VARCHAR(20) DEFAULT NULL,
            applied_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            expires_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT fk_enchantment_player_item FOREIGN KEY (player_item_id) REFERENCES player_item(id) ON DELETE CASCADE
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_enchantment_player_item ON enchantment (player_item_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_enchantment_expires_at ON enchantment (expires_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS enchantment');
    }
}
