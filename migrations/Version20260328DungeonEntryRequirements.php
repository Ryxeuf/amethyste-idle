<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328DungeonEntryRequirements extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add entry_requirements JSON column to game_dungeons for item-based access gating';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE game_dungeons ADD COLUMN IF NOT EXISTS entry_requirements JSON DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_dungeons DROP COLUMN IF EXISTS entry_requirements');
    }
}
