<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328ToolSlots extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add unlocked_tool_slots JSON column to player table for craft/gathering tool equipment slots';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE player ADD COLUMN IF NOT EXISTS unlocked_tool_slots JSON DEFAULT '[]' NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS unlocked_tool_slots');
    }
}
