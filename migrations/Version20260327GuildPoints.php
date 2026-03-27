<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260327GuildPoints extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add points column to guild table for global ranking';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            ALTER TABLE guild ADD COLUMN IF NOT EXISTS points INT NOT NULL DEFAULT 0
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_guild_points ON guild (points DESC)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_guild_points');
        $this->addSql('ALTER TABLE guild DROP COLUMN IF EXISTS points');
    }
}
