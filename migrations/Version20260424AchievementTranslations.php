<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260424AchievementTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add title_translations and description_translations JSON columns to game_achievements (task 135 sous-phase 3e.c.achievement)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_achievements ADD COLUMN IF NOT EXISTS title_translations JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE game_achievements ADD COLUMN IF NOT EXISTS description_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_achievements DROP COLUMN IF EXISTS title_translations');
        $this->addSql('ALTER TABLE game_achievements DROP COLUMN IF EXISTS description_translations');
    }
}
