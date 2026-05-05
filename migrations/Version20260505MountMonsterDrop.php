<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260505MountMonsterDrop extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add drop_monster_id FK + drop_probability on game_mounts (task 130 sous-phase 2b.loot — mount drop from monster)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_mounts ADD COLUMN IF NOT EXISTS drop_monster_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE game_mounts ADD COLUMN IF NOT EXISTS drop_probability INT NOT NULL DEFAULT 0');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_mount_drop_monster') THEN
                    ALTER TABLE game_mounts
                        ADD CONSTRAINT fk_mount_drop_monster
                        FOREIGN KEY (drop_monster_id) REFERENCES game_monsters (id) ON DELETE SET NULL;
                END IF;
            END $$;
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_mount_drop_monster ON game_mounts (drop_monster_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_mount_drop_monster');
        $this->addSql('ALTER TABLE game_mounts DROP CONSTRAINT IF EXISTS fk_mount_drop_monster');
        $this->addSql('ALTER TABLE game_mounts DROP COLUMN IF EXISTS drop_probability');
        $this->addSql('ALTER TABLE game_mounts DROP COLUMN IF EXISTS drop_monster_id');
    }
}
