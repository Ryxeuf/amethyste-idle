<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324DailyQuests extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_daily flag on game_quests and create daily_quest_selection table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS is_daily BOOLEAN NOT NULL DEFAULT FALSE');

        $this->addSql('CREATE TABLE IF NOT EXISTS daily_quest_selection (
            id SERIAL PRIMARY KEY,
            quest_id INT NOT NULL REFERENCES game_quests(id),
            date DATE NOT NULL
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS daily_quest_selection_unique ON daily_quest_selection (quest_id, date)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS daily_quest_selection');
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS is_daily');
    }
}
