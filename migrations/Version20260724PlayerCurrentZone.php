<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260724PlayerCurrentZone extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add player.current_zone_id + backfill from zone.source_map_id (pivot PBBG, ZON-03 — player position becomes a zone)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player ADD COLUMN IF NOT EXISTS current_zone_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_player_current_zone ON player (current_zone_id)');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_player_current_zone') THEN
                    ALTER TABLE player
                        ADD CONSTRAINT fk_player_current_zone
                        FOREIGN KEY (current_zone_id) REFERENCES zone (id) ON DELETE SET NULL;
                END IF;
            END $$;
        SQL);

        // Backfill 1 : zone derivee de la carte courante du joueur.
        $this->addSql(<<<'SQL'
            UPDATE player p SET current_zone_id = z.id
            FROM zone z
            WHERE z.source_map_id = p.map_id AND z.enabled = TRUE AND p.current_zone_id IS NULL
        SQL);

        // Backfill 2 : joueurs sur une carte sans zone (donjon instancie, carte de
        // test) → rattaches au hub. No-op si le hub n'est pas encore seede.
        $this->addSql(<<<'SQL'
            UPDATE player p SET current_zone_id = hub.id
            FROM (SELECT id FROM zone WHERE slug = 'village-de-lumiere' AND enabled = TRUE LIMIT 1) hub
            WHERE p.current_zone_id IS NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE player DROP CONSTRAINT IF EXISTS fk_player_current_zone');
        $this->addSql('DROP INDEX IF EXISTS idx_player_current_zone');
        $this->addSql('ALTER TABLE player DROP COLUMN IF EXISTS current_zone_id');
    }
}
