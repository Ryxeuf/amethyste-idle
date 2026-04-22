<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260422SkillTitleTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add title_translations JSON column to game_skills (task 135 sous-phase 3e.c.skill)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_skills ADD COLUMN IF NOT EXISTS title_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_skills DROP COLUMN IF EXISTS title_translations');
    }
}
