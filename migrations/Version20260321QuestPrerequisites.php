<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321QuestPrerequisites extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add prerequisite_quests JSON column to game_quests table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS prerequisite_quests JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS prerequisite_quests');
    }
}
