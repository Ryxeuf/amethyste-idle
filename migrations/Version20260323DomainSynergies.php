<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260323DomainSynergies extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create game_domain_synergies table for cross-domain synergy bonuses';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS game_domain_synergies (
            id SERIAL PRIMARY KEY,
            domain_a_id INTEGER NOT NULL REFERENCES game_domains(id),
            domain_b_id INTEGER NOT NULL REFERENCES game_domains(id),
            name VARCHAR(128) NOT NULL,
            description TEXT NOT NULL,
            bonus_type VARCHAR(32) NOT NULL,
            bonus_value INTEGER NOT NULL,
            activation_threshold INTEGER NOT NULL DEFAULT 50,
            CONSTRAINT uq_synergy_domains UNIQUE (domain_a_id, domain_b_id)
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_synergy_domain_a ON game_domain_synergies (domain_a_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_synergy_domain_b ON game_domain_synergies (domain_b_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS game_domain_synergies');
    }
}
