<?php

namespace App\Tests\Integration\Migrations;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Toute migration doit viser une table qui existe.
 *
 * Le 2026-08-02, la production est partie en boucle de redemarrage : l'entree
 * du conteneur applique les migrations au demarrage (`set -e`), et
 * `Version20260729MRaceDropStatModifiers` visait `race` la ou la table
 * s'appelle `game_races`. Le defaut a vecu quatre jours sur `main` sans que
 * rien ne le voie.
 *
 * POURQUOI CE TEST PLUTOT QU'UN REJEU DES MIGRATIONS
 *
 * La CI monte son schema avec `doctrine:schema:create` ; les migrations ne sont
 * jouees nulle part. Le reflexe serait de les rejouer sur une base vierge —
 * sauf que **la chaine ne sait pas construire la base depuis zero** : la toute
 * premiere migration, `Version20260313AddUserIsBanned`, fait
 * `ALTER TABLE "users"` sur une table qu'aucune migration ne cree (le schema
 * initial est venu de `doctrine:schema:update`). Un rejeu integral echoue donc
 * des la premiere ligne, pour une raison historique et sans rapport.
 *
 * Ce test attrape la meme classe de defaut sans base de donnees : il compare
 * les tables citees par les migrations a celles que connait la cartographie
 * Doctrine, plus celles que les migrations creent elles-memes.
 */
class MigrationTableNamesTest extends KernelTestCase
{
    /**
     * Tables reelles qu'aucune entite ne declare et qu'aucune migration ne cree.
     *
     * `users` precede les migrations (schema initial). Les autres appartiennent
     * a Doctrine et a Messenger.
     */
    private const KNOWN_UNMAPPED_TABLES = [
        'users',
        'doctrine_migration_versions',
        'messenger_messages',
    ];

    public function testEveryMigrationTargetsAKnownTable(): void
    {
        self::bootKernel();

        $known = $this->knownTables();
        $offenders = [];

        foreach ($this->migrationFiles() as $path) {
            $source = file_get_contents($path);
            if (false === $source) {
                continue;
            }

            foreach ($this->tablesReferencedBy($source) as $table) {
                if (!\in_array($table, $known, true)) {
                    $offenders[] = sprintf('%s -> %s', basename($path), $table);
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            "Ces migrations visent une table qui n'existe pas. En production, la "
            . "premiere qui echoue arrete l'entree du conteneur et le service ne "
            . "demarre plus :\n  " . implode("\n  ", $offenders)
        );
    }

    /**
     * Tables connues : celles de la cartographie Doctrine (entites et tables de
     * jointure), celles que les migrations creent, et les exceptions listees.
     *
     * @return list<string>
     */
    private function knownTables(): array
    {
        /** @var EntityManagerInterface $em */
        $em = self::getContainer()->get(EntityManagerInterface::class);

        $tables = self::KNOWN_UNMAPPED_TABLES;

        // On passe par SchemaTool plutot que par les metadonnees a la main : il
        // rend le schema tel que Doctrine le creerait, tables de jointure
        // comprises (`player_skill` n'appartient a aucune entite).
        $schema = (new SchemaTool($em))->getSchemaFromMetadata(
            $em->getMetadataFactory()->getAllMetadata()
        );

        foreach ($schema->getTables() as $table) {
            $name = $table->getName();
            // Une table peut etre qualifiee par son schema (`public.foo`).
            $dot = strrpos($name, '.');
            $tables[] = $this->normalize(false === $dot ? $name : substr($name, $dot + 1));
        }

        // Une migration peut creer une table de travail que plus aucune entite
        // ne porte : la citer ensuite est legitime.
        foreach ($this->migrationFiles() as $path) {
            $source = file_get_contents($path);
            if (false === $source) {
                continue;
            }

            preg_match_all(
                '/CREATE\s+TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?["`\']?(\w+)/i',
                $source,
                $matches
            );
            foreach ($matches[1] as $table) {
                $tables[] = $this->normalize($table);
            }
        }

        return array_values(array_unique($tables));
    }

    /**
     * Tables citees par une migration, en ecriture.
     *
     * `DROP TABLE` est volontairement exclu : supprimer une table d'un schema
     * ancien, que plus aucune entite ne declare, est le cas nominal.
     *
     * @return list<string>
     */
    private function tablesReferencedBy(string $source): array
    {
        preg_match_all(
            '/(?:ALTER\s+TABLE(?:\s+IF\s+EXISTS)?|INSERT\s+INTO|UPDATE|DELETE\s+FROM)\s+["`\']?(\w+)/i',
            $source,
            $matches
        );

        $tables = array_map(fn (string $table): string => $this->normalize($table), $matches[1]);

        // `UPDATE` attrape aussi le fragment `ON UPDATE CASCADE` d'une contrainte.
        $tables = array_filter($tables, static fn (string $table): bool => !\in_array(
            $table,
            ['cascade', 'restrict', 'set', 'no'],
            true
        ));

        return array_values(array_unique($tables));
    }

    private function normalize(string $table): string
    {
        return strtolower(trim($table, '"`\' '));
    }

    /**
     * @return list<string>
     */
    private function migrationFiles(): array
    {
        $files = glob(\dirname(__DIR__, 3) . '/migrations/Version*.php');

        return false === $files ? [] : array_values($files);
    }
}
