<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327HiddenAchievements extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add hidden column to game_achievements for secret achievements';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE game_achievements ADD COLUMN IF NOT EXISTS hidden BOOLEAN NOT NULL DEFAULT FALSE
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_achievements DROP COLUMN IF EXISTS hidden');
    }
}
