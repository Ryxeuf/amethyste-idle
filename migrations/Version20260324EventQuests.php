<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324EventQuests extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game_event_id to game_quests for event-linked quests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS game_event_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_game_quests_game_event') THEN
                    ALTER TABLE game_quests ADD CONSTRAINT fk_game_quests_game_event
                        FOREIGN KEY (game_event_id) REFERENCES game_event(id) ON DELETE SET NULL;
                END IF;
            END $$
        SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_quests_game_event_id ON game_quests (game_event_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests DROP CONSTRAINT IF EXISTS fk_game_quests_game_event');
        $this->addSql('DROP INDEX IF EXISTS idx_game_quests_game_event_id');
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS game_event_id');
    }
}
