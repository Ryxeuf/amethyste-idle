<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325EquipmentSets extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create equipment sets and set bonuses tables, add equipment_set_id FK to game_items';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_equipment_sets (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(100) NOT NULL,
            name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            CONSTRAINT uniq_equipment_set_slug UNIQUE (slug)
        )');

        $this->addSql('CREATE TABLE IF NOT EXISTS game_equipment_set_bonuses (
            id SERIAL PRIMARY KEY,
            equipment_set_id INT NOT NULL,
            required_pieces INT NOT NULL,
            bonus_type VARCHAR(50) NOT NULL,
            bonus_value INT NOT NULL,
            CONSTRAINT fk_set_bonus_set FOREIGN KEY (equipment_set_id) REFERENCES game_equipment_sets (id) ON DELETE CASCADE,
            CONSTRAINT uniq_set_pieces UNIQUE (equipment_set_id, required_pieces)
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_set_bonus_set_id ON game_equipment_set_bonuses (equipment_set_id)');

        $this->addSql('ALTER TABLE game_items ADD COLUMN IF NOT EXISTS equipment_set_id INT DEFAULT NULL');

        $this->addSql('DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_item_equipment_set\') THEN
                ALTER TABLE game_items ADD CONSTRAINT fk_item_equipment_set FOREIGN KEY (equipment_set_id) REFERENCES game_equipment_sets (id) ON DELETE SET NULL;
            END IF;
        END $$');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_item_equipment_set_id ON game_items (equipment_set_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items DROP CONSTRAINT IF EXISTS fk_item_equipment_set');
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS equipment_set_id');
        $this->addSql('DROP TABLE IF EXISTS game_equipment_set_bonuses');
        $this->addSql('DROP TABLE IF EXISTS game_equipment_sets');
    }
}
