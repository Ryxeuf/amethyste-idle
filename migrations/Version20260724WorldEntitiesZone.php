<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724WorldEntitiesZone extends AbstractMigration
{
    private const TABLES = ['mob', 'pnj', 'object_layer'];

    public function getDescription(): string
    {
        return 'Add zone_id on mob/pnj/object_layer + backfill from zone.source_map_id (pivot PBBG, ZON-04 — world entities attached to zones)';
    }

    public function up(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %s ADD COLUMN IF NOT EXISTS zone_id INT DEFAULT NULL', $table));
            $this->addSql(sprintf('CREATE INDEX IF NOT EXISTS idx_%s_zone ON %s (zone_id)', $table, $table));
            $this->addSql(sprintf(<<<'SQL'
                DO $$ BEGIN
                    IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_%1$s_zone') THEN
                        ALTER TABLE %1$s
                            ADD CONSTRAINT fk_%1$s_zone
                            FOREIGN KEY (zone_id) REFERENCES zone (id) ON DELETE SET NULL;
                    END IF;
                END $$;
            SQL, $table));
            // Backfill : zone derivee de la carte. Les entites sur une carte hors
            // graphe (donjon instancie, carte de test) restent volontairement a NULL.
            $this->addSql(sprintf(<<<'SQL'
                UPDATE %1$s t SET zone_id = z.id
                FROM zone z
                WHERE z.source_map_id = t.map_id AND z.enabled = TRUE AND t.zone_id IS NULL
            SQL, $table));
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::TABLES as $table) {
            $this->addSql(sprintf('ALTER TABLE %1$s DROP CONSTRAINT IF EXISTS fk_%1$s_zone', $table));
            $this->addSql(sprintf('DROP INDEX IF EXISTS idx_%s_zone', $table));
            $this->addSql(sprintf('ALTER TABLE %s DROP COLUMN IF EXISTS zone_id', $table));
        }
    }
}
