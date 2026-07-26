<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou des references de quete (tache 128c).
 *
 * Une quete cite ses cibles par **slug** : un monstre a tuer, une zone a
 * atteindre. Aucun de ces slugs n'est resolu au chargement des fixtures — ils
 * ne servent qu'au suivi de progression, a l'execution.
 *
 * Un slug errone ne casse donc rien : la quete est acceptable, elle
 * s'affiche, et son objectif ne se valide **jamais**. C'est le meme motif que
 * les alertes de danger des monstres (128a) et les ingredients introuvables
 * (128b) : de la donnee declarative que plus rien ne relie a du comportement.
 */
class QuestReferenceTest extends TestCase
{
    private function questSource(): string
    {
        return (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/QuestFixtures.php');
    }

    /**
     * Chaque monstre cible par une quete existe.
     */
    public function testEveryTargetedMonsterExists(): void
    {
        $monsters = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/MonsterFixtures.php');
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $monsters, $known);

        $source = $this->questSource();
        $targeted = [];
        preg_match_all("/'monsters' => \[(.*?)\n                \]/s", $source, $blocks);
        foreach ($blocks[1] as $block) {
            preg_match_all("/'slug' => '([a-z_0-9]+)'/", $block, $slugs);
            $targeted = array_merge($targeted, $slugs[1]);
        }

        $this->assertNotEmpty($targeted, 'Le test ne verifie rien si l\'extraction echoue.');
        $this->assertSame(
            [],
            array_values(array_diff(array_unique($targeted), $known[1])),
            'Une quete demande de tuer un monstre qui n\'existe pas : son objectif ne se validera jamais.',
        );
    }

    /**
     * Chaque zone ciblee par une etape d'exploration existe dans le graphe.
     *
     * Ne concerne que la forme **cible** `zone_slug`. La forme heritee
     * (`map_id` + coordonnees) reste toleree par `PlayerQuestUpdater`, mais
     * elle exige une carte d'origine — que les zones nees depuis ZON-26b
     * n'ont pas.
     */
    public function testEveryTargetedZoneExists(): void
    {
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 3));
        $definition = $loader->loadFile($loader->defaultFile());
        $known = array_column($definition['zones'], 'slug');

        preg_match_all("/'zone_slug' => '([a-z0-9-]+)'/", $this->questSource(), $targeted);

        $this->assertNotEmpty($targeted[1], 'Le test ne verifie rien si aucune quete ne cible de zone.');
        $this->assertSame(
            [],
            array_values(array_diff(array_unique($targeted[1]), $known)),
            'Une quete demande d\'atteindre une zone qui n\'existe pas : son objectif ne se validera jamais.',
        );
    }
}
