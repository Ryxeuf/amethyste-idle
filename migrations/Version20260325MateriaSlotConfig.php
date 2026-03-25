<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325MateriaSlotConfig extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add materia_slot_config JSON column to game_items for per-slot element configuration';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM information_schema.columns
                    WHERE table_name = 'game_items' AND column_name = 'materia_slot_config'
                ) THEN
                    ALTER TABLE game_items ADD COLUMN materia_slot_config JSON DEFAULT NULL;
                END IF;
            END $$
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_items DROP COLUMN IF EXISTS materia_slot_config');
    }
}
