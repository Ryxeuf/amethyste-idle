<?php

namespace App\Tests\Unit\DataFixtures;

use App\GameEngine\Zone\ZoneDefinitionLoader;
use PHPUnit\Framework\TestCase;

/**
 * Loi transverse : un gate de filon designe une competence qui existe (ECO-24c).
 *
 * `GatherService` n'avait **aucun** gate de competence : il rendait les filons
 * d'une zone sans jamais consulter le joueur (BALANCE §21.5). Les six
 * competences hautes de l'arbre du mineur declaraient des `spot-*` de l'ancien
 * systeme de carte — un chemin que le pivot PBBG a laisse sans porte d'entree —
 * et ne gataient donc plus rien. Le gate vit desormais dans la donnee de zone.
 *
 * Le defaut que ce test empeche est muet, comme toute la famille (ECO-02,
 * ECO-19, ECO-24b) : une faute de frappe dans `requires_skill:` ne ferait pas
 * planter le moteur, elle rendrait le filon **definitivement** inaccessible —
 * personne ne peut apprendre une competence qui n'existe pas.
 */
class VeinSkillGateTest extends TestCase
{
    private function root(): string
    {
        return \dirname(__DIR__, 3);
    }

    /**
     * @return array{zones: list<array<string, mixed>>, connections: list<array<string, mixed>>}
     */
    private function world(): array
    {
        $loader = new ZoneDefinitionLoader($this->root());

        return $loader->loadFile($loader->defaultFile());
    }

    /**
     * Slug de competence exige => slugs de filons qui l'exigent.
     *
     * @return array<string, list<string>>
     */
    private function gatesBySkill(): array
    {
        $gates = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ((array) ($zone['gather'] ?? []) as $resource) {
                if (!isset($resource['requires_skill'])) {
                    continue;
                }
                $gates[(string) $resource['requires_skill']][] = (string) $resource['slug'];
            }
        }

        return $gates;
    }

    /**
     * Slugs de competence livres par les fixtures.
     *
     * @return list<string>
     */
    private function knownSkillSlugs(): array
    {
        $source = (string) file_get_contents($this->root() . '/src/DataFixtures/Game/SkillFixtures.php');
        preg_match_all("/'slug' => '([a-z0-9-]+)'/", $source, $matches);

        return array_values(array_unique($matches[1]));
    }

    public function testEveryVeinGateNamesAnExistingSkill(): void
    {
        $gates = $this->gatesBySkill();
        $this->assertNotEmpty($gates, 'Aucun filon gate : le test ne verifie rien.');

        $known = $this->knownSkillSlugs();
        $this->assertNotEmpty($known, 'Le test ne verifie rien si l\'extraction des competences echoue.');

        $unknown = array_values(array_diff(array_keys($gates), $known));

        $this->assertSame(
            [],
            $unknown,
            'Ces filons exigent une competence qui n\'existe dans aucun arbre : personne ne pourra jamais les '
            . 'exploiter, et rien ne le signalera.',
        );
    }

    /**
     * Le gate est **opt-in** : le plancher de l'economie ne se ferme jamais.
     *
     * Meme decision que le gate de services des foyers (FOY-05, decision A) :
     * rien de ce qui etait accessible ne se ferme. Un filon T0 ou T1 gate
     * couperait un debutant du cuivre, de l'etain ou de la menthe — c'est-a-dire
     * de la premiere recette qu'il rencontre.
     */
    public function testNoEntryTierVeinIsGated(): void
    {
        $offenders = [];
        foreach ($this->world()['zones'] as $zone) {
            foreach ((array) ($zone['gather'] ?? []) as $resource) {
                if (!isset($resource['requires_skill'])) {
                    continue;
                }
                // T0 et T1 sont les deux profils dont la capacite atteint 60
                // (cf. tableau de calibrage en tete de `world_1.yaml`).
                if ((int) $resource['capacity'] >= 60) {
                    $offenders[] = (string) $resource['slug'];
                }
            }
        }

        $this->assertSame(
            [],
            $offenders,
            'Ces filons de palier d\'entree sont gates. Le plancher de l\'economie ne doit jamais dependre d\'un '
            . 'arbre de talent : un debutant y perdrait sa premiere recette sans comprendre pourquoi.',
        );
    }

    /**
     * Une competence ne garde jamais deux fois la meme porte.
     *
     * Un minerai de haut palier n'a qu'une source (`OreSourceReferenceTest`) :
     * deux filons derriere la meme competence signaleraient soit un doublon de
     * source, soit un copier-coller de gate.
     */
    public function testNoSkillGatesTwoVeins(): void
    {
        $duplicated = array_keys(array_filter(
            $this->gatesBySkill(),
            static fn (array $veins): bool => \count($veins) > 1,
        ));

        $this->assertSame(
            [],
            $duplicated,
            'Ces competences gatent plusieurs filons : verifier qu\'il ne s\'agit pas d\'un gate recopie.',
        );
    }
}
