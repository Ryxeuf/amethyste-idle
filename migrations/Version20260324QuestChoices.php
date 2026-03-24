<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324QuestChoices extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add choice_outcome on game_quests and choice_made on player_quest_completed for branching quests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS choice_outcome JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE player_quest_completed ADD COLUMN IF NOT EXISTS choice_made VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS choice_outcome');
        $this->addSql('ALTER TABLE player_quest_completed DROP COLUMN IF EXISTS choice_made');
    }
}
