<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * `zone.source_map_primary` — quelle zone une carte designe (pivot PBBG, ZON-04).
 *
 * Le lien zone -> carte est un « plusieurs vers un » assume : le Fanal et son
 * Quartier des Jardins partagent la meme carte. Le lien **inverse** n'avait, lui,
 * aucune reponse definie, alors que trois chemins en dependent — le rattachement
 * automatique des entites de monde (`WorldEntityZoneListener`), le rattrapage des
 * orphelines et la synchronisation de la zone d'un joueur.
 *
 * La colonne naît a `false` : c'est `app:zone:import` qui pose la verite depuis
 * le YAML, et le deploiement le joue desormais juste apres les migrations. Le
 * repli d'ici la (`ORDER BY source_map_primary DESC, id ASC`) reproduit le
 * comportement le plus probable d'avant, en le rendant au moins **stable**.
 */
final class Version20260806CZoneSourceMapPrimary extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add zone.source_map_primary — designates which zone a shared source map belongs to (ZON-04)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone ADD COLUMN IF NOT EXISTS source_map_primary BOOLEAN NOT NULL DEFAULT FALSE');

        // Une carte qui ne porte qu'une seule zone activee n'a aucune ambiguite
        // a lever : sa zone est principale par construction. Le poser ici evite
        // que le repli par `id` ait quoi que ce soit a decider dans le cas de
        // loin le plus courant (onze cartes sur douze).
        $this->addSql(<<<'SQL'
            UPDATE zone z SET source_map_primary = TRUE
            WHERE z.source_map_id IS NOT NULL
              AND z.enabled = TRUE
              AND NOT EXISTS (
                  SELECT 1 FROM zone o
                  WHERE o.source_map_id = z.source_map_id
                    AND o.enabled = TRUE
                    AND o.id <> z.id
              )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE zone DROP COLUMN IF EXISTS source_map_primary');
    }
}
