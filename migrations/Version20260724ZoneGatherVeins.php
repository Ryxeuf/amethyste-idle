<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Recolte par zone & filons partages (pivot PBBG, ZON-10).
 *
 * - zone.gather_config : table declarative des ressources recoltables par zone.
 * - zone_vein : stock collectif runtime par (zone, ressource), qui s'epuise et
 *   respawn (fenetre de tension cooperative).
 */
final class Version20260724ZoneGatherVeins extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zone.gather_config JSON column and zone_vein table (pivot PBBG, ZON-10 — shared depleting veins per zone)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone ADD COLUMN IF NOT EXISTS gather_config JSON DEFAULT NULL');

        $this->addSql(<<<'SQL'
            CREATE TABLE IF NOT EXISTS zone_vein (
                id SERIAL NOT NULL,
                zone_id INT NOT NULL,
                slug VARCHAR(64) NOT NULL,
                stock INT NOT NULL,
                depleted_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
                PRIMARY KEY(id)
            )
            SQL);

        $this->addSql('CREATE INDEX IF NOT EXISTS idx_zone_vein_zone ON zone_vein (zone_id)');
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_zone_vein_zone_slug ON zone_vein (zone_id, slug)');

        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_zone_vein_zone') THEN
                    ALTER TABLE zone_vein
                        ADD CONSTRAINT fk_zone_vein_zone
                        FOREIGN KEY (zone_id) REFERENCES zone (id)
                        ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE;
                END IF;
            END $$
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS zone_vein');
        $this->addSql('ALTER TABLE zone DROP COLUMN IF EXISTS gather_config');
    }
}
