<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327DungeonRunOrigin extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add origin_map_id and origin_coordinates to dungeon_run for teleport back after completion';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dungeon_run ADD COLUMN IF NOT EXISTS origin_map_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE dungeon_run ADD COLUMN IF NOT EXISTS origin_coordinates VARCHAR(20) DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_dungeon_run_origin_map') THEN
                    ALTER TABLE dungeon_run ADD CONSTRAINT fk_dungeon_run_origin_map FOREIGN KEY (origin_map_id) REFERENCES map(id) ON DELETE SET NULL;
                END IF;
            END $$
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE dungeon_run DROP CONSTRAINT IF EXISTS fk_dungeon_run_origin_map');
        $this->addSql('ALTER TABLE dungeon_run DROP COLUMN IF EXISTS origin_map_id');
        $this->addSql('ALTER TABLE dungeon_run DROP COLUMN IF EXISTS origin_coordinates');
    }
}
