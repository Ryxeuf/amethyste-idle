<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Garde-fou des evenements de boss de zone (tache 128d).
 *
 * Un evenement `boss_spawn` est servi par **deux** gestionnaires, selon ce
 * qu'il declare :
 *
 * - `WorldBossManager` prend la main quand l'evenement porte `map_id` et
 *   `coordinates` — il fait apparaitre une creature a un endroit precis ;
 * - `ZoneBossManager` prend la main quand l'evenement porte une **zone** — il
 *   ouvre un combat collectif a barre partagee (ZON-18).
 *
 * Un evenement qui ne satisfait **ni l'un ni l'autre** s'active quand meme,
 * s'annonce aux joueurs par Mercure, et ne fait apparaitre personne. Selon le
 * manque, il sort en silence complet ou laisse un simple avertissement de
 * journal — ce qui suppose que quelqu'un les lise.
 *
 * Un garde-fou coute moins cher qu'une annonce mensongere.
 */
class ZoneBossEventTest extends TestCase
{
    /**
     * @return list<array{key: string, block: string}>
     */
    private function bossSpawnEvents(): array
    {
        $source = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/GameEventFixtures.php');

        $events = [];
        preg_match_all("/'(event_[a-z_0-9]+)' => \[(.*?)\n            \],/s", $source, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            if (!str_contains($match[2], 'TYPE_BOSS_SPAWN')) {
                continue;
            }
            $events[] = ['key' => $match[1], 'block' => $match[2]];
        }

        return $events;
    }

    public function testEveryBossSpawnEventHasAHandler(): void
    {
        $events = $this->bossSpawnEvents();
        $this->assertNotEmpty($events, 'Aucun evenement de boss livre : le test ne verifie rien.');

        $monsters = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/MonsterFixtures.php');
        preg_match_all("/\n            '([a-z_0-9]+)' => \[/", $monsters, $knownMonsters);

        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 3));
        $knownZones = array_column($loader->loadFile($loader->defaultFile())['zones'], 'slug');

        foreach ($events as $event) {
            $key = $event['key'];
            $block = $event['block'];

            // Les deux gestionnaires exigent un monstre, quel que soit le chemin.
            $this->assertSame(
                1,
                preg_match("/'monster_slug' => '([a-z_0-9]+)'/", $block, $monster),
                sprintf('L\'evenement "%s" n\'indique aucun monstre : aucun boss ne naitra.', $key),
            );
            $this->assertContains(
                $monster[1],
                $knownMonsters[1],
                sprintf('L\'evenement "%s" cible un monstre inexistant : seul un avertissement de journal le dira.', $key),
            );

            // Chemin world boss : un endroit precis suffit, la zone en decoule.
            if (str_contains($block, "'map_id'") && str_contains($block, "'coordinates'")) {
                continue;
            }

            // Sinon, c'est un boss de zone — et il lui faut une zone.
            $this->assertSame(
                1,
                preg_match("/'zone' => '([a-z0-9-]+)'/", $block, $zone),
                sprintf(
                    'L\'evenement "%s" n\'a ni carte (world boss) ni zone (boss de zone) : aucun gestionnaire ne le prendra, et il s\'annoncera quand meme.',
                    $key,
                ),
            );
            $this->assertContains($zone[1], $knownZones, sprintf('L\'evenement "%s" cible une zone inexistante.', $key));
        }
    }
}
