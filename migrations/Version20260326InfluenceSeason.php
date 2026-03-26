<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326InfluenceSeason extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create influence_season table (GCC-06)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS influence_season (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            season_number INT NOT NULL,
            starts_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
            ends_at TIMESTAMP WITHOUT TIME ZONE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT \'scheduled\',
            theme VARCHAR(100) DEFAULT NULL,
            parameters JSON DEFAULT NULL,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT uq_influence_season_slug UNIQUE (slug),
            CONSTRAINT uq_influence_season_number UNIQUE (season_number)
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_influence_season_status ON influence_season (status)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS influence_season');
    }
}
