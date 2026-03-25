<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326HiddenQuests extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add isHidden and triggerCondition fields to game_quests for hidden discovery quests';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS is_hidden BOOLEAN NOT NULL DEFAULT false');
        $this->addSql('ALTER TABLE game_quests ADD COLUMN IF NOT EXISTS trigger_condition JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS is_hidden');
        $this->addSql('ALTER TABLE game_quests DROP COLUMN IF EXISTS trigger_condition');
    }
}
