<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260328RegionUpgrades extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create region_upgrade table for guild city upgrades (GCC-13)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS region_upgrade (
                id SERIAL PRIMARY KEY,
                region_control_id INT NOT NULL REFERENCES region_control(id) ON DELETE CASCADE,
                upgrade_slug VARCHAR(50) NOT NULL,
                level INT NOT NULL DEFAULT 1,
                cost_gils INT NOT NULL,
                activated_at TIMESTAMP NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT NOW(),
                updated_at TIMESTAMP NOT NULL DEFAULT NOW(),
                CONSTRAINT uniq_region_upgrade_control_slug UNIQUE (region_control_id, upgrade_slug)
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE INDEX IF NOT EXISTS idx_region_upgrade_control ON region_upgrade (region_control_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS region_upgrade');
    }
}
