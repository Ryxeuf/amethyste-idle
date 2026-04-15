<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260415QuestMinRenownScore extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add min_renown_score column to game_quests (task 121 - renown-gated special quests)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS min_renown_score INTEGER DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS min_renown_score');
    }
}
