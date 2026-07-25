<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * NAR-07 : credit de guilde sur les faits de monde du Codex.
 */
final class Version20260725CodexWorldFacts extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'NAR-07: credited_guild_name on game_codex_entries (world facts guild credit)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_codex_entries ADD COLUMN IF NOT EXISTS credited_guild_name VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE game_codex_entries DROP COLUMN IF EXISTS credited_guild_name');
    }
}
