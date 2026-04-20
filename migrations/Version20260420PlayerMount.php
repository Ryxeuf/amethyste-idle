<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420PlayerMount extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player_mount ownership table (task 130 sous-phase 2)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS player_mount (
            id SERIAL NOT NULL,
            player_id INT NOT NULL,
            mount_id INT NOT NULL,
            acquired_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            source VARCHAR(32) NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_player_mount ON player_mount (player_id, mount_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_mount_player ON player_mount (player_id)');
        $this->addSql('DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_player_mount_player\') THEN
                ALTER TABLE player_mount ADD CONSTRAINT fk_player_mount_player
                    FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE;
            END IF;
        END $$');
        $this->addSql('DO $$ BEGIN
            IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = \'fk_player_mount_mount\') THEN
                ALTER TABLE player_mount ADD CONSTRAINT fk_player_mount_mount
                    FOREIGN KEY (mount_id) REFERENCES game_mounts (id) ON DELETE CASCADE;
            END IF;
        END $$');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_mount');
    }
}
