<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260725GroupDungeonCombat extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add shared turn-based combat state to group_dungeon_run (pivot PBBG, ZON-19 sub-milestone 2)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_dungeon_run ADD COLUMN IF NOT EXISTS encounter_hp_max INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE group_dungeon_run ADD COLUMN IF NOT EXISTS encounter_hp_current INT DEFAULT 0 NOT NULL');
        $this->addSql("ALTER TABLE group_dungeon_run ADD COLUMN IF NOT EXISTS turn_order JSON DEFAULT '[]' NOT NULL");
        $this->addSql('ALTER TABLE group_dungeon_run ADD COLUMN IF NOT EXISTS active_turn_index INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE group_dungeon_run ADD COLUMN IF NOT EXISTS turn_deadline TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE group_dungeon_run DROP COLUMN IF EXISTS turn_deadline');
        $this->addSql('ALTER TABLE group_dungeon_run DROP COLUMN IF EXISTS active_turn_index');
        $this->addSql('ALTER TABLE group_dungeon_run DROP COLUMN IF EXISTS turn_order');
        $this->addSql('ALTER TABLE group_dungeon_run DROP COLUMN IF EXISTS encounter_hp_current');
        $this->addSql('ALTER TABLE group_dungeon_run DROP COLUMN IF EXISTS encounter_hp_max');
    }
}
