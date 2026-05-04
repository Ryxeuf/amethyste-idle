<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260504PlayerActiveMount extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add active_mount_id FK on player (task 130 sous-phase 3a — fondation monture active)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS active_mount_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_active_mount') THEN
                    ALTER TABLE player
                        ADD CONSTRAINT fk_player_active_mount
                        FOREIGN KEY (active_mount_id) REFERENCES game_mounts (id) ON DELETE SET NULL;
                END IF;
            END $$;
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_active_mount ON player (active_mount_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_player_active_mount');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT IF EXISTS fk_player_active_mount');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS active_mount_id');
    }
}
