<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260423QuestDescriptionTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description_translations JSON column to game_quests (task 135 sous-phase 3e.c.d.quest.d)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS description_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS description_translations');
    }
}
