<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260320GameEvent extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create game_event table for event scheduling';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_event (
            id SERIAL PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT \'custom\',
            description TEXT DEFAULT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'scheduled\',
            starts_at TIMESTAMP NOT NULL,
            ends_at TIMESTAMP NOT NULL,
            parameters JSON DEFAULT NULL,
            recurring BOOLEAN NOT NULL DEFAULT FALSE,
            recurrence_interval INTEGER DEFAULT NULL,
            map_id INTEGER DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            CONSTRAINT fk_game_event_map FOREIGN KEY (map_id) REFERENCES map(id) ON DELETE SET NULL
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_event_status ON game_event(status)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_event_type ON game_event(type)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_game_event_starts_at ON game_event(starts_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS game_event');
    }
}
