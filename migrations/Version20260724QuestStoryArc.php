<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724QuestStoryArc extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add game_quests.story_arc + arc_order columns and story_arc index (NAR-01 — marqueur d\'arc narratif)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS story_arc VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS arc_order INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_quests_story_arc ON game_quests (story_arc)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_game_quests_story_arc');
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS arc_order');
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS story_arc');
    }
}
