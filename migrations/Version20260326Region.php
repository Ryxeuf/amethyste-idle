<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260326Region extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create region table and add region_id FK on map (GCC-05)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS region (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL,
            description TEXT DEFAULT NULL,
            icon VARCHAR(50) DEFAULT NULL,
            tax_rate NUMERIC(5,4) NOT NULL DEFAULT 0.0500,
            is_contestable BOOLEAN NOT NULL DEFAULT TRUE,
            capital_map_id INT DEFAULT NULL,
            created_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMP WITHOUT TIME ZONE NOT NULL DEFAULT NOW(),
            CONSTRAINT uq_region_slug UNIQUE (slug),
            CONSTRAINT fk_region_capital_map FOREIGN KEY (capital_map_id) REFERENCES map(id) ON DELETE SET NULL
        )');

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_region_capital_map ON region (capital_map_id)');

        $this->addSql('ALTER TABLE map ADD COLUMN IF NOT EXISTS region_id INT DEFAULT NULL');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_map_region') THEN
                    ALTER TABLE map ADD CONSTRAINT fk_map_region FOREIGN KEY (region_id) REFERENCES region(id) ON DELETE SET NULL;
                END IF;
            END $$
        SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_map_region ON map (region_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE map DROP CONSTRAINT IF EXISTS fk_map_region');
        $this->addSql('ALTER TABLE map DROP COLUMN IF EXISTS region_id');
        $this->addSql('DROP TABLE IF EXISTS region');
    }
}
