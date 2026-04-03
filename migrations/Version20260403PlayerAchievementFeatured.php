<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260403PlayerAchievementFeatured extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add featured column to player_achievements for profile showcase';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_achievements ADD COLUMN IF NOT EXISTS featured BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_achievements DROP COLUMN IF EXISTS featured');
    }
}
