<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260316FightLog extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add fight_log table for persistent combat logging';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE fight_log (
            id SERIAL PRIMARY KEY,
            fight_id INTEGER NOT NULL REFERENCES fight(id) ON DELETE CASCADE,
            turn INTEGER NOT NULL,
            actor_type VARCHAR(20) NOT NULL,
            actor_id INTEGER DEFAULT NULL,
            actor_name VARCHAR(255) NOT NULL,
            type VARCHAR(30) NOT NULL,
            message TEXT NOT NULL,
            metadata JSON DEFAULT NULL,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE INDEX idx_fight_log_fight_turn ON fight_log (fight_id, turn)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS fight_log');
    }
}
