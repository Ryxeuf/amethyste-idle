<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Les Dunes d'Ambre cessent d'etre la zone la plus pauvre du monde (ZON-31).
 *
 * Elle avait **un filon utile et quatre creatures**, dont aucune ne rendait
 * quoi que ce soit au depeceur — alors que la ligne cuir/os du desert est sa
 * production declaree (GAME_ZONES § 2.7). Ce fichier verrouille les trois
 * proprietes qui la reparent.
 *
 * 1. **L'ambre est une matiere, plus un nom de lieu**, et son gisement
 *    n'existe nulle part ailleurs (loi 1).
 * 2. **Une exclusivite sans debouche est un mensonge de level design** : le
 *    sceau consomme l'ambre, sinon le filon existerait pour rien.
 * 3. **Le desert se depece en os**, pas en cuir souple — c'est l'autre moitie
 *    de la ligne du tanneur, et c'est ce qui distingue les Dunes des Vallons.
 */
class AmberDunesTest extends TestCase
{
    private const SLUG = 'dunes-d-ambre';

    /**
     * @return array{zones: list<array<string, mixed>>, connections: list<array<string, mixed>>}
     */
    private function world(): array
    {
        $loader = new ZoneDefinitionLoader(\dirname(__DIR__, 3));

        return $loader->loadFile($loader->defaultFile());
    }

    /**
     * @return array<string, mixed>
     */
    private function dunes(): array
    {
        foreach ($this->world()['zones'] as $zone) {
            if (self::SLUG === $zone['slug']) {
                return $zone;
            }
        }

        self::fail('Les Dunes d\'Ambre sont absentes du monde.');
    }

    /**
     * L'ambre fossile ne se ramasse **que** dans les Dunes.
     *
     * « L'Ambre » etait un nom de region sans matiere derriere. La loi 1 veut
     * qu'une zone ait une source exclusive ; c'est celle des Dunes.
     */
    public function testFossilAmberComesFromTheDunesAndNowhereElse(): void
    {
        $sources = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ($zone['gather'] ?? [] as $resource) {
                if ('amber-fossil' === $resource['item']) {
                    $sources[] = $zone['slug'];
                }
            }
        }

        self::assertSame([self::SLUG], $sources);
    }

    /**
     * L'ambre affleure : aucun gate de competence.
     *
     * Les metaux de haut palier se gagnent sur un savoir (ECO-24c) ; l'ambre
     * non. Le gater fermerait la seule raison de descendre au sud avant
     * l'Acte III, et la zone resterait ce qu'elle etait — un lieu de passage.
     */
    public function testAmberIsNotLockedBehindASkill(): void
    {
        foreach ($this->dunes()['gather'] as $resource) {
            if ('amber-fossil' === $resource['item']) {
                self::assertArrayNotHasKey('requires_skill', $resource);

                return;
            }
        }

        self::fail('Aucun gisement d\'ambre dans les Dunes.');
    }

    /**
     * L'exclusivite a un **debouche**.
     *
     * Un filon dont la matiere n'est consommee par rien est une promesse vide :
     * le joueur descend, extrait, et decouvre qu'il n'y a rien a en faire.
     */
    public function testTheAmberIsActuallyConsumedBySomething(): void
    {
        $recipes = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/RecipeFixtures.php');

        self::assertStringContainsString("['slug' => 'amber-fossil'", $recipes);
    }

    /**
     * Et la recette qui la consomme est **atteignable** : un arbre de talent la
     * debloque. Une recette qu'aucun skill n'ouvre est du contenu mort (ECO-18).
     */
    public function testTheAmberRecipeIsUnlockedBySomeSkill(): void
    {
        $skills = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/Game/SkillFixtures.php');

        self::assertStringContainsString("'recipe-amber-seal'", $skills);
    }

    /**
     * Le desert se depece en **os**.
     *
     * C'est ce qui distingue sa moitie de la ligne du tanneur de celle des
     * Vallons : le sable seche ce qu'il prend. Sans une espece qui en rende,
     * la « ligne cuir/os du desert » n'etait qu'une phrase de document.
     */
    public function testTheDesertFaunaYieldsBone(): void
    {
        $loot = (string) file_get_contents(\dirname(__DIR__, 3) . '/src/DataFixtures/MonsterItemFixtures.php');

        $boneBearers = 0;
        foreach ($this->dunes()['mobs'] as $mob) {
            if (str_contains($loot, sprintf("['monster' => '%s', 'item' => 'leather_bone'", $mob['monster']))) {
                ++$boneBearers;
            }
        }

        self::assertGreaterThanOrEqual(2, $boneBearers, 'La ligne de l\'os du desert tient sur trop peu d\'especes.');
    }

    /**
     * La zone n'est plus la plus pauvre du monde.
     *
     * Le compte est epingle volontairement : c'etait le diagnostic du jalon
     * (« un filon, quatre monstres »), et le laisser retomber la sous ce seuil
     * defairait tout ce que ZON-31 a fait.
     */
    public function testTheDunesAreNoLongerThePoorestZone(): void
    {
        $dunes = $this->dunes();

        self::assertGreaterThanOrEqual(4, \count($dunes['gather']));
        self::assertGreaterThanOrEqual(6, \count($dunes['mobs']));
    }

    /**
     * Le gisement d'ambre suit le profil T3 du calibrage.
     *
     * C'est une matiere qu'on **vient chercher**, pas une qu'on ramasse en
     * passant : lui donner un tampon de zone de depart aurait efface la
     * distance qui fait tout son prix.
     */
    public function testTheAmberSeamFollowsTheRareProfile(): void
    {
        foreach ($this->dunes()['gather'] as $resource) {
            if ('amber-fossil' === $resource['item']) {
                self::assertSame(24, $resource['capacity']);
                self::assertSame(36000, $resource['respawn_seconds']);

                return;
            }
        }

        self::fail('Aucun gisement d\'ambre dans les Dunes.');
    }
}
