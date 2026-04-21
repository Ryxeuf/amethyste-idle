<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260421SeasonRewardCosmeticIcon extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add cosmetic_icon column to player_season_reward (task 132 sous-phase 4b.2)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_season_reward ADD COLUMN IF NOT EXISTS cosmetic_icon VARCHAR(60) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player_season_reward DROP COLUMN IF EXISTS cosmetic_icon');
    }
}
