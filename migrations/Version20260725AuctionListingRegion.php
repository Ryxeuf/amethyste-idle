<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * ECO-03 : segmentation regionale de l'hotel des ventes.
 *
 * Deux changements indissociables :
 *
 * 1. `auction_listing.region_id` — le marche ou l'annonce a ete deposee, fige au
 *    depot. Backfill depuis la position du vendeur, zone d'abord (source de
 *    verite depuis le pivot), carte en repli.
 *
 * 2. Rattachement des quatre cartes sauvages a une region. Sans cela la
 *    segmentation n'aurait rien segmente : une seule region portait des cartes,
 *    et un joueur en foret ou aux mines n'appartenait a aucun marche.
 */
final class Version20260725AuctionListingRegion extends AbstractMigration
{
    /**
     * Rattachement par **nom de carte** : les identifiants ne sont pas stables
     * d'un environnement a l'autre.
     */
    private const MAP_REGIONS = [
        'Forêt des murmures' => 'plaines-eveil',
        'Mines profondes' => 'terres-sauvages',
        'Marais Brumeux' => 'terres-sauvages',
        'Crête de Ventombre' => 'terres-sauvages',
    ];

    public function getDescription(): string
    {
        return 'Add auction_listing.region_id and attach wilderness maps to regions (ECO-03)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE auction_listing ADD COLUMN IF NOT EXISTS region_id INT DEFAULT NULL');
        $this->addSql(<<<'SQL'
            DO $$ BEGIN
                IF NOT EXISTS (SELECT 1 FROM pg_constraint WHERE conname = 'fk_auction_listing_region') THEN
                    ALTER TABLE auction_listing
                        ADD CONSTRAINT fk_auction_listing_region
                        FOREIGN KEY (region_id) REFERENCES region (id) ON DELETE SET NULL;
                END IF;
            END $$;
            SQL);
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_auction_listing_region ON auction_listing (region_id, status, expires_at)');

        // Rattachement des cartes sauvages : une carte deja rattachee n'est pas
        // touchee, la donnee existante fait foi.
        foreach (self::MAP_REGIONS as $mapName => $regionSlug) {
            $this->addSql(
                'UPDATE map SET region_id = (SELECT id FROM region WHERE slug = :slug)
                 WHERE name = :name AND region_id IS NULL
                   AND EXISTS (SELECT 1 FROM region WHERE slug = :slug)',
                ['slug' => $regionSlug, 'name' => $mapName]
            );
        }

        // Backfill des annonces existantes : zone courante du vendeur d'abord.
        $this->addSql(<<<'SQL'
            UPDATE auction_listing l
            SET region_id = m.region_id
            FROM player p
            JOIN zone z ON z.id = p.current_zone_id
            JOIN map m ON m.id = z.source_map_id
            WHERE p.id = l.seller_id AND l.region_id IS NULL AND m.region_id IS NOT NULL
            SQL);

        // Repli pour les vendeurs sans zone : leur carte de rattachement.
        $this->addSql(<<<'SQL'
            UPDATE auction_listing l
            SET region_id = m.region_id
            FROM player p
            JOIN map m ON m.id = p.map_id
            WHERE p.id = l.seller_id AND l.region_id IS NULL AND m.region_id IS NOT NULL
            SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_auction_listing_region');
        $this->addSql('ALTER TABLE auction_listing DROP CONSTRAINT IF EXISTS fk_auction_listing_region');
        $this->addSql('ALTER TABLE auction_listing DROP COLUMN IF EXISTS region_id');

        // Le rattachement des cartes n'est pas defait : il corrige une donnee
        // manquante, pas un choix reversible, et d'autres systemes (controle de
        // cite, taxe) s'appuient desormais dessus.
    }
}
