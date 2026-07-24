<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724ZoneGraph extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create zone + zone_connection tables (pivot PBBG, ZON-02 — world as a zone graph)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS zone (
                id SERIAL NOT NULL,
                slug VARCHAR(64) NOT NULL,
                name VARCHAR(255) NOT NULL,
                name_translations JSON DEFAULT NULL,
                description TEXT DEFAULT NULL,
                description_translations JSON DEFAULT NULL,
                illustration_path VARCHAR(255) DEFAULT NULL,
                type VARCHAR(32) NOT NULL DEFAULT 'wilderness',
                is_safe BOOLEAN NOT NULL DEFAULT FALSE,
                enabled BOOLEAN NOT NULL DEFAULT TRUE,
                source_map_id INT DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_zone_slug ON zone (slug)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_zone_source_map ON zone (source_map_id)');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_zone_source_map') THEN
                    ALTER TABLE zone
                        ADD CONSTRAINT fk_zone_source_map
                        FOREIGN KEY (source_map_id) REFERENCES map (id) ON DELETE SET NULL;
                END IF;
            END $$;
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS zone_connection (
                id SERIAL NOT NULL,
                from_zone_id INT NOT NULL,
                to_zone_id INT NOT NULL,
                travel_seconds INT NOT NULL DEFAULT 60,
                requires_discovery BOOLEAN NOT NULL DEFAULT FALSE,
                enabled BOOLEAN NOT NULL DEFAULT TRUE,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
        SQL);
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_zone_connection_from_to ON zone_connection (from_zone_id, to_zone_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_zone_connection_from ON zone_connection (from_zone_id)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_zone_connection_to ON zone_connection (to_zone_id)');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_zone_connection_from') THEN
                    ALTER TABLE zone_connection
                        ADD CONSTRAINT fk_zone_connection_from
                        FOREIGN KEY (from_zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_zone_connection_to') THEN
                    ALTER TABLE zone_connection
                        ADD CONSTRAINT fk_zone_connection_to
                        FOREIGN KEY (to_zone_id) REFERENCES zone (id) ON DELETE CASCADE;
                END IF;
            END $$;
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS zone_connection');
        $this->addSql('DROP TABLE IF EXISTS zone');
    }
}
