<?php

namespace App\Tests\Unit\DataFixtures;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Le perimetre de l'acte I expose les cinq recoltes (ONB-10).
 *
 * Ferme la dette **D11**. Le Fanal n'exposait que **deux filons, tous deux
 * d'herboristerie**, alors que l'acte I fait choisir un parchemin de recolte
 * **parmi cinq** (etape 6). Un choix qui debouche sur une seule recolte
 * possible est un faux choix : tout le monde serait devenu herboriste, et
 * l'indicateur de repartition des metiers (ONB-19) l'aurait constate trop tard.
 *
 * Le perimetre est **le Fanal et ses voisins immediats**, au sens du graphe :
 * une seule liaison. C'est ce que le premier voyage offert rend atteignable
 * sans attendre.
 */
class ActOnePerimeterTest extends TestCase
{
    private const HUB = 'village-de-lumiere';

    /**
     * Les cinq recoltes de GAME_ONBOARDING § 5.2, etape 6.
     *
     * Le depecage n'est pas un filon : il se prend sur la faune, ce qui lui
     * vaut un traitement a part plus bas.
     */
    private const VEIN_PROFESSIONS = ['herbalism', 'mining', 'woodcutting', 'fishing'];

    /**
     * Quatre des cinq recoltes s'atteignent par un filon du perimetre.
     */
    public function testTheFourVeinProfessionsAreReachableWithinActOne(): void
    {
        $reachable = [];
        foreach ($this->perimeter() as $zoneKey) {
            foreach ($this->world()['zones'][$zoneKey]['gather'] ?? [] as $vein) {
                // Un filon gate sur competence de haut palier ne compte pas :
                // il n'est pas atteignable au moment ou l'acte I le demande.
                if (isset($vein['requires_skill'])) {
                    continue;
                }
                $reachable[$vein['profession']][] = $zoneKey;
            }
        }

        $missing = array_values(array_diff(self::VEIN_PROFESSIONS, array_keys($reachable)));

        self::assertSame([], $missing, sprintf(
            "Ces recoltes n'ont aucune source dans le perimetre de l'acte I : %s.\n"
            . "L'etape 6 fait choisir un parchemin parmi cinq — un choix sans debouche est un faux choix (D11).",
            implode(', ', $missing),
        ));
    }

    /**
     * Le **depecage** a son terrain d'apprentissage dans le perimetre.
     *
     * Il ne se recolte pas sur un filon mais sur la faune, et les Vallons
     * d'Aubepine sont la zone-ecole du depeceur (ZON-30). La loi verifie donc
     * qu'au moins une zone du perimetre declare une population.
     */
    public function testSkinningHasItsSchoolWithinActOne(): void
    {
        $withFauna = [];
        foreach ($this->perimeter() as $zoneKey) {
            if (($this->world()['zones'][$zoneKey]['mobs'] ?? []) !== []) {
                $withFauna[] = $zoneKey;
            }
        }

        self::assertNotEmpty($withFauna, 'Aucune zone du perimetre de l\'acte I ne declare de faune : le depeceur n\'a nulle part ou apprendre.');
    }

    /**
     * Le hub n'est plus mono-metier.
     *
     * C'est la formulation exacte de la dette : deux filons, tous deux
     * d'herboristerie. Le hub n'a pas a porter les cinq — mais il ne doit plus
     * n'en porter qu'une, sinon le premier geste de recolte oriente.
     */
    public function testTheHubExposesMoreThanOneProfession(): void
    {
        $professions = [];
        foreach ($this->world()['zones'][self::HUB]['gather'] ?? [] as $vein) {
            $professions[$vein['profession']] = true;
        }

        self::assertGreaterThan(1, \count($professions), sprintf(
            'Le Fanal n\'expose qu\'une seule recolte (%s) : le premier geste orienterait tout le monde vers le meme metier.',
            implode(', ', array_keys($professions)),
        ));
    }

    /**
     * Le Fanal reste **sur**.
     *
     * `safe: true` force `mob: 0` dans `ExploreService`. Ajouter des filons ne
     * doit pas servir de pretexte a y lever la surete — le combat s'y enseigne
     * sur des mannequins (ONB-11), precisement pour ne pas avoir a la lever.
     */
    public function testTheHubStaysSafe(): void
    {
        self::assertTrue($this->world()['zones'][self::HUB]['safe'] ?? false, 'Le Fanal n\'est plus une zone sure.');
        self::assertSame([], $this->world()['zones'][self::HUB]['mobs'] ?? [], 'Le Fanal declare une population : elle serait inerte, et le dire serait mentir.');
    }

    /**
     * Le hub et ses voisins directs.
     *
     * @return list<string>
     */
    private function perimeter(): array
    {
        $zones = [self::HUB];

        foreach ($this->world()['connections'] ?? [] as $connection) {
            if (($connection['from'] ?? null) === self::HUB) {
                $zones[] = $connection['to'];
            } elseif (($connection['to'] ?? null) === self::HUB && ($connection['bidirectional'] ?? false)) {
                $zones[] = $connection['from'];
            }
        }

        $known = array_keys($this->world()['zones'] ?? []);

        return array_values(array_intersect(array_unique($zones), $known));
    }

    /**
     * @return array<string, mixed>
     */
    private function world(): array
    {
        /** @var array<string, mixed> $parsed */
        $parsed = Yaml::parseFile(\dirname(__DIR__, 3) . '/config/game/zones/world_1.yaml');

        return $parsed;
    }
}
