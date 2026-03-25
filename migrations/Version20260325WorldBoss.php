<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260325WorldBoss extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add world boss columns to mob table (is_world_boss, game_event_id)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob ADD COLUMN IF NOT EXISTS is_world_boss BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE mob ADD COLUMN IF NOT EXISTS game_event_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_mob_game_event') THEN
                    ALTER TABLE mob ADD CONSTRAINT fk_mob_game_event FOREIGN KEY (game_event_id) REFERENCES game_event(id) ON DELETE SET NULL;
                END IF;
            END $$
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_mob_game_event ON mob (game_event_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE mob DROP CONSTRAINT IF EXISTS fk_mob_game_event');
        $this->addSql('DROP INDEX IF EXISTS idx_mob_game_event');
        $this->addSql('ALTER TABLE mob DROP COLUMN IF EXISTS game_event_id');
        $this->addSql('ALTER TABLE mob DROP COLUMN IF EXISTS is_world_boss');
    }
}
