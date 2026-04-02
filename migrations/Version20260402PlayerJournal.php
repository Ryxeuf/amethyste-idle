<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260402PlayerJournal extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create player_journal_entry table for player activity journal';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS player_journal_entry (
                id SERIAL PRIMARY KEY,
                player_id INT NOT NULL,
                type VARCHAR(30) NOT NULL,
                message VARCHAR(255) NOT NULL,
                metadata JSON DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
                CONSTRAINT fk_journal_player FOREIGN KEY (player_id) REFERENCES player (id) ON DELETE CASCADE
            )
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_journal_player_date ON player_journal_entry (player_id, created_at)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_journal_type ON player_journal_entry (type)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS player_journal_entry');
    }
}
