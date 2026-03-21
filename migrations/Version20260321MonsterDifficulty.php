<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260321MonsterDifficulty extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add difficulty column (1-5) to game_monsters for visual difficulty indicator';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monsters ADD COLUMN IF NOT EXISTS difficulty INTEGER DEFAULT 1 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_monsters DROP COLUMN IF EXISTS difficulty');
    }
}
