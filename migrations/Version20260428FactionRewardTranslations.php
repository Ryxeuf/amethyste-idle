<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260428FactionRewardTranslations extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add label_translations and description_translations JSON columns to game_faction_rewards (task 135 sous-phase 3e.h.b)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_faction_rewards ADD COLUMN IF NOT EXISTS label_translations JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE game_faction_rewards ADD COLUMN IF NOT EXISTS description_translations JSON DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_faction_rewards DROP COLUMN IF EXISTS label_translations');
        $this->addSql('ALTER TABLE game_faction_rewards DROP COLUMN IF EXISTS description_translations');
    }
}
