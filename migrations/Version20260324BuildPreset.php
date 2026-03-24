<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260324BuildPreset extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create build_preset table for saved skill configurations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS build_preset (
            id SERIAL PRIMARY KEY,
            player_id INT NOT NULL REFERENCES player(id) ON DELETE CASCADE,
            name VARCHAR(50) NOT NULL,
            skill_slugs JSON NOT NULL DEFAULT \'[]\',
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL DEFAULT NOW()
        )');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_build_preset_player ON build_preset (player_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS build_preset');
    }
}
