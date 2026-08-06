<?php

namespace App\GameEngine\Zone;

use Doctrine\DBAL\Connection;

/**
 * Rattache a leur zone les entites de monde restees orphelines (pivot PBBG, ZON-04).
 *
 * **Pourquoi ce service existe.** Une entite de monde atteint sa zone par deux
 * chemins qui n'ont pas la meme fiabilite :
 *
 * - la population **declaree** (`ZoneImporter::syncPnjs`/`syncMobs`) pose la zone
 *   **directement** — elle ne peut pas manquer ;
 * - tout le reste (fixtures, administration, invasions, invocations) ne pose
 *   qu'une **carte**, et `WorldEntityZoneListener` en **derive** la zone en
 *   `prePersist` via `zone.source_map_id`.
 *
 * Le second chemin ne marche que si la zone existait deja, avec sa carte
 * d'origine, au moment ou l'entite a ete persistee. Quand ce n'est pas le cas,
 * `zone_id` reste `NULL` **pour toujours** : le seul rattrapage ecrit jusqu'ici
 * etait le backfill unique de `Version20260724WorldEntitiesZone`, et une
 * migration ne se rejoue pas. C'est ainsi que la maitresse d'armes du Fanal —
 * premiere porte de la chaine de l'acte I — a disparu de l'ecran de zone sans
 * qu'aucune erreur ne soit levee : l'ecran liste **strictement** par zone, donc
 * une entite orpheline n'est pas « mal placee », elle n'existe plus.
 *
 * Le rattrapage est donc un **geste rejouable**, appele a la fois par
 * `app:zone:audit --fix` et par `app:zone:import` — ce dernier etant precisement
 * le moment ou `zone.source_map_id` vient d'etre ecrit, c'est-a-dire le premier
 * instant ou le rattachement devient possible.
 *
 * Trois garde-fous tiennent par construction :
 * - on ne touche **que** les lignes a `NULL` — une zone posee explicitement
 *   (donjon instancie, deplacement de joueur) n'est jamais ecrasee ;
 * - on ne rattache qu'a une zone **activee** ;
 * - une carte hors graphe (carte de test, donjon) ne rattache rien, et c'est
 *   voulu : ces entites comptent comme « hors graphe », jamais comme orphelines.
 */
class WorldEntityZoneBackfiller
{
    /**
     * Table => colonne portant la zone.
     *
     * `player` figure ici parce qu'un joueur sans zone n'a plus **aucune action
     * disponible** : c'est la meme panne, un cran plus grave.
     *
     * @var array<string, string>
     */
    public const TABLES = [
        'player' => 'current_zone_id',
        'mob' => 'zone_id',
        'pnj' => 'zone_id',
        'object_layer' => 'zone_id',
    ];

    /**
     * Tables dont une entite ne se deplace **jamais** d'elle-meme entre deux
     * zones d'une meme carte — donc les seules qu'on ait le droit de remettre a
     * leur place quand elles ont atterri dans la mauvaise.
     *
     * `player` en est exclu, et ce n'est pas un oubli : un joueur change de zone
     * parce qu'il l'a decide (`ZoneTravelService`). Le « corriger » le
     * teleporterait hors du lotissement ou il est peut-etre en train de payer
     * son loyer.
     *
     * @var list<string>
     */
    private const STATIC_TABLES = ['mob', 'pnj', 'object_layer'];

    /**
     * Zone principale de chaque carte, une ligne par carte.
     *
     * `DISTINCT ON` est ce qui rend le rattachement **deterministe** : sans lui,
     * un `UPDATE ... FROM zone` sur une carte partagee (le Fanal en est une)
     * choisit une zone au hasard des lignes.
     */
    private const PRIMARY_ZONE_PER_MAP = <<<'SQL'
        SELECT DISTINCT ON (source_map_id) source_map_id, id
        FROM zone
        WHERE enabled = TRUE AND source_map_id IS NOT NULL
        ORDER BY source_map_id, source_map_primary DESC, id ASC
        SQL;

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * Rattache les orphelines de chaque table depuis leur carte, et ramene les
     * entites fixes egarees dans une autre zone de leur propre carte.
     *
     * @return array<string, int> table => nombre de lignes rattachees (les tables
     *                            sans rien a faire sont omises)
     */
    public function backfill(): array
    {
        $fixed = [];

        foreach (self::TABLES as $table => $column) {
            $rows = (int) $this->connection->executeStatement(sprintf(
                <<<'SQL'
                UPDATE %1$s t SET %2$s = z.id
                FROM (%3$s) z
                WHERE z.source_map_id = t.map_id AND t.%2$s IS NULL
                SQL,
                $table,
                $column,
                self::PRIMARY_ZONE_PER_MAP,
            ));

            if (\in_array($table, self::STATIC_TABLES, true)) {
                $rows += $this->reattachMisplaced($table, $column);
            }

            if ($rows > 0) {
                $fixed[$table] = $rows;
            }
        }

        return $fixed;
    }

    /**
     * Ramene a la zone principale de leur carte les entites fixes posees dans
     * une **autre zone de cette meme carte**.
     *
     * Un `zone_id` non nul n'est pas une preuve de bonne place : quand deux
     * zones partagent une carte, le listener de rattachement en choisissait une
     * au hasard. Un simple backfill des `NULL` ne repare donc rien — les
     * habitants du Fanal etaient rattaches, seulement pas la ou on les cherche.
     *
     * Deux bornes rendent le geste sur : on ne touche qu'une entite dont la zone
     * actuelle **partage la carte** de l'entite (une creature invoquee ailleurs,
     * un calque deplace a la main, ne bougent pas), et la population declaree
     * est hors d'atteinte par construction — `ZoneImporter::syncPnjs()` pose la
     * zone sans jamais poser de carte, donc `map_id IS NULL` et la jointure ne
     * la rencontre pas.
     */
    private function reattachMisplaced(string $table, string $column): int
    {
        return (int) $this->connection->executeStatement(sprintf(
            <<<'SQL'
            UPDATE %1$s t SET %2$s = z.id
            FROM (%3$s) z
            WHERE z.source_map_id = t.map_id
              AND t.%2$s IS NOT NULL
              AND t.%2$s <> z.id
              AND EXISTS (
                  SELECT 1 FROM zone cur
                  WHERE cur.id = t.%2$s AND cur.source_map_id = t.map_id
              )
            SQL,
            $table,
            $column,
            self::PRIMARY_ZONE_PER_MAP,
        ));
    }

    /**
     * Etat de rattachement, table par table.
     *
     * Deux chiffres doivent etre nuls : `orphans` (carte zonee, aucune zone
     * posee) et `misplaced` (posee dans une **autre** zone de sa propre carte —
     * le defaut qu'un simple backfill des `NULL` ne voyait pas, puisque la ligne
     * n'etait pas vide, juste fausse). `off_graph` compte les entites sur une
     * carte qu'aucune zone ne prend pour origine : un etat legitime.
     *
     * La jointure passe par `DISTINCT ON` et non directement par `zone` : sur
     * une carte partagee, une jointure nue **duplique chaque ligne**, et le
     * total annonce se met a compter des entites qui n'existent pas.
     *
     * @return array<string, array{total: int, with_zone: int, orphans: int, misplaced: int, off_graph: int}>
     */
    public function stats(): array
    {
        $stats = [];

        foreach (self::TABLES as $table => $column) {
            // La zone actuelle est **jointe** plutot que testee par un `EXISTS` :
            // PostgreSQL refuse une sous-requete dans le `FILTER` d'un agregat.
            $misplaced = \in_array($table, self::STATIC_TABLES, true)
                ? sprintf(
                    'COUNT(*) FILTER (WHERE t.%1$s IS NOT NULL AND z.id IS NOT NULL AND t.%1$s <> z.id AND cur.source_map_id = t.map_id)',
                    $column,
                )
                : '0';

            $row = $this->connection->fetchAssociative(sprintf(
                <<<'SQL'
                SELECT
                    COUNT(*) AS total,
                    COUNT(t.%2$s) AS with_zone,
                    COUNT(*) FILTER (WHERE t.%2$s IS NULL AND z.id IS NOT NULL) AS orphans,
                    %4$s AS misplaced,
                    COUNT(*) FILTER (WHERE t.%2$s IS NULL AND z.id IS NULL) AS off_graph
                FROM %1$s t
                LEFT JOIN (%3$s) z ON z.source_map_id = t.map_id
                LEFT JOIN zone cur ON cur.id = t.%2$s
                SQL,
                $table,
                $column,
                self::PRIMARY_ZONE_PER_MAP,
                $misplaced,
            ));

            /** @var array<string, mixed> $row */
            $row = \is_array($row) ? $row : [];

            $stats[$table] = [
                'total' => (int) ($row['total'] ?? 0),
                'with_zone' => (int) ($row['with_zone'] ?? 0),
                'orphans' => (int) ($row['orphans'] ?? 0),
                'misplaced' => (int) ($row['misplaced'] ?? 0),
                'off_graph' => (int) ($row['off_graph'] ?? 0),
            ];
        }

        return $stats;
    }

    /**
     * Entites mal rattachees, toutes tables confondues : orphelines + egarees.
     */
    public function countBroken(): int
    {
        $stats = $this->stats();

        return array_sum(array_column($stats, 'orphans'))
            + array_sum(array_column($stats, 'misplaced'));
    }
}
