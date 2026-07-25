<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * NAR-05 : Codex (foyer de la trame de monde) — definitions d'entrees et
 * deblocages par joueur.
 */
final class Version20260725CodexEntries extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'NAR-05: Codex entries (game_codex_entries) + per-player unlocks (player_codex_entry)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_codex_entries (
            id SERIAL PRIMARY KEY,
            slug VARCHAR(255) NOT NULL,
            category VARCHAR(50) NOT NULL,
            title VARCHAR(255) NOT NULL,
            title_translations JSON DEFAULT NULL,
            description TEXT NOT NULL,
            description_translations JSON DEFAULT NULL,
            unlock_type VARCHAR(50) NOT NULL,
            unlock_key VARCHAR(255) DEFAULT NULL,
            illustration_path VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_codex_entries_slug ON game_codex_entries(slug)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_codex_entries_category ON game_codex_entries(category)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_codex_entries_unlock ON game_codex_entries(unlock_type, unlock_key)');

        $this->addSql('CREATE TABLE IF NOT EXISTS player_codex_entry (
            id SERIAL PRIMARY KEY,
            player_id INTEGER NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            codex_entry_id INTEGER NOT NULL REFERENCES game_codex_entries(id) ON DELETE CASCADE,
            unlocked_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT uniq_player_codex_entry UNIQUE(player_id, codex_entry_id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_codex_entry_player ON player_codex_entry(player_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_codex_entry');
        $this->addSql('DROP TABLE IF EXISTS game_codex_entries');
    }
}
