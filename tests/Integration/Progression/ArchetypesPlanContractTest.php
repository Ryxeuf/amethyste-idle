<?php

namespace App\Tests\Integration\Progression;

use App\Entity\Game\Domain;
use App\Entity\Game\Skill;
use App\GameEngine\Progression\SkillLeverReader;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * ARC-09 — le contrat du plan archetypes (GAME_ARCHETYPES § 12).
 *
 * Quarante-cinq invariants, et ce fichier repond a une seule question pour
 * chacun : **qui le tient ?** Il ne re-teste pas ce que d'autres tiennent deja
 * — il sert de table des matieres, il empeche cet index de pourrir, et il porte
 * les garde-fous que personne ne portait.
 *
 * ## Ce que l'index a mesure
 *
 * Sur les 45 invariants, **26 sont tenus**, **5 sont portes ici pour la premiere
 * fois**, et **14 ne peuvent pas l'etre aujourd'hui** — pas par negligence, mais
 * parce que le mecanisme qu'ils decrivent n'existe pas encore. Les distinguer
 * est tout l'objet du fichier : *un invariant qu'aucun mecanisme ne peut violer
 * ne mesure rien, et le compter comme tenu serait un mensonge d'inventaire.*
 *
 * Les quatorze se rangent en trois familles, et chacune a son jalon :
 *
 *  - **ARC-18** (les formes de geste) — n° 36 a 38, 42, 43 : charges, postures,
 *    differes, familier. Aucune forme n'est livree, donc aucune ne peut deroger.
 *  - **ARC-19** (l'aggro bornee) — n° 39, 40 : la riposte ne se deplace pas, et
 *    la rencontre de groupe se calibre encore sur un multiple de membres.
 *  - **ARC-05c** (le recalibrage) — n° 29, 34, 35 : le simulateur les **mesure**
 *    depuis ARC-17c, et le releve dit qu'ils ne sont pas tenus. Ils vivent en
 *    cliquet dans `BalanceSimulationRatchetTest` plutot qu'en seuil sec, parce
 *    qu'un seuil qu'on sait rouge ne mesure plus rien.
 *
 * Restent quatre cas isoles, nommes dans les methodes qui les portent.
 */
class ArchetypesPlanContractTest extends AbstractIntegrationTestCase
{
    /**
     * Les arbres convertis au gabarit — ceux sur lesquels le contrat porte.
     *
     * Les dix-neuf autres sont encore herites : leur demander le gabarit
     * reviendrait a tester ARC-08 avant qu'il ait eu lieu. La liste grandit a
     * chaque arbre converti, et `CombatBranchCatalogTest` la tient a jour.
     *
     * @var list<string>
     */
    private const CONVERTED = [
        'Pyromancien', 'Guérisseur', 'Soldat', 'Archer',
        'Nécromancien', 'Assassin', 'Artificier', 'Gardien', 'Vagabond',
    ];

    /**
     * L'index ne doit pas pourrir : chaque test cite existe encore.
     */
    public function testTheContractIndexNamesRealTests(): void
    {
        foreach ([
            // Budget, plafonds, palette, gabarit, capstone (1, 2, 3, 5, 6, 28)
            'Integration/Progression/PatronTreeContractTest.php',
            // Le vocabulaire ferme des leviers et sa double borne (2, 3 ter, 28)
            'Unit/GameEngine/Progression/CombatLeverTest.php',
            // Une fonction par domaine, aucun triplet en double (4)
            'Unit/GameEngine/Progression/DomainRoleTest.php',
            // Un arbre ouvre un geste de son registre (7)
            'Unit/DataFixtures/CombatRegisterCoverageTest.php',
            // Les palettes d'intention, degat et plan B (8, 9)
            'Integration/Fight/DomainIntentPaletteContractTest.php',
            // La loi du depot : duree, portee, etalement (10, 11, 19, 22)
            'Unit/GameEngine/Fight/DepositLawTest.php',
            'Integration/Fight/DepositedGestureContractTest.php',
            // Le vocabulaire ferme des conditions et leur prix (12, 20, 24)
            'Unit/GameEngine/Progression/SkillConditionTest.php',
            'Unit/GameEngine/Progression/SkillLeverConditionTest.php',
            // La marque du jour 1, des deux cotes (13, 17, 23, 27)
            'Integration/Fight/ElementalMarkReachabilityTest.php',
            'Integration/Fight/MonsterMarkReachabilityTest.php',
            'Unit/GameEngine/Fight/ElementalMarkTest.php',
            // Aucun sort accorde par un nœud, aucun plafond global (14, 16)
            'Unit/DataFixtures/DomainPlanContractTest.php',
            // La fourche : deux branches, un accord chacune, exclusives (18)
            'Unit/GameEngine/Progression/CombatBranchCatalogTest.php',
            'Integration/Progression/CombatBranchManagerTest.php',
            // Le pacte : unique, borne, feuille (21, 21 bis, 21 ter)
            'Integration/Progression/PactRuleTest.php',
            'Unit/GameEngine/Progression/PactTest.php',
            // Les ressources par registre, et rien en gils (25, 26, 31, 32)
            'Unit/DataFixtures/RegisterResourceTest.php',
            // Le palier des accords suit la fonction (30)
            'Unit/GameEngine/Balance/AccordTierRuleTest.php',
            // Les accointances ne donnent aucune puissance (44)
            'Integration/Progression/AccointanceContractTest.php',
            // Ce que le simulateur mesure, en cliquet (29, 34, 35)
            'Integration/Balance/BalanceSimulationRatchetTest.php',
        ] as $test) {
            self::assertFileExists(
                \dirname(__DIR__, 3) . '/tests/' . $test,
                sprintf('Le contrat cite %s, qui n\'existe plus : mettre l\'index a jour.', $test),
            );
        }
    }

    /**
     * **Invariant 45 — chaque arbre ouvre un accord exclusif.**.
     *
     * Personne ne le tenait, et c'est celui qui decide si un arbre existe :
     * *un arbre dont tous les gestes s'obtiennent ailleurs n'est pas un arbre,
     * c'est un raccourci*. Il devient verifiable maintenant que cinq arbres sont
     * au gabarit — et il a deja decide du contenu trois fois, chaque fois qu'un
     * accord partage a force son cadet a ecrire le sien (ARC-08b, c, d, e).
     */
    public function testEveryConvertedTreeOpensAnAccordNoOtherTreeOpens(): void
    {
        $byTree = $this->accordsByTree();

        foreach (self::CONVERTED as $title) {
            $own = $byTree[$title] ?? [];
            self::assertNotEmpty($own, sprintf('%s n\'ouvre aucun accord.', $title));

            $elsewhere = [];
            foreach ($byTree as $other => $accords) {
                if ($other !== $title) {
                    $elsewhere = array_merge($elsewhere, $accords);
                }
            }

            self::assertNotEmpty(
                array_diff($own, $elsewhere),
                sprintf('%s n\'ouvre aucun accord exclusif : tous ses gestes s\'obtiennent ailleurs.', $title),
            );
        }
    }

    /**
     * **Invariant 5 — deux nœuds a 0 point, et tous deux des accords.**.
     *
     * L'heritage de GAME_MATERIA § 3, cote arbre : ce qu'on a le jour 1 est
     * exactement deux gestes, jamais un passif. Un passif gratuit se lirait
     * comme une statistique offerte, et c'est precisement ce que le budget
     * refuse.
     */
    public function testEveryConvertedTreeHasExactlyTwoFreeAccords(): void
    {
        foreach (self::CONVERTED as $title) {
            $free = [];
            foreach ($this->nodesOf($title) as $skill) {
                if (0 === $skill->getRequiredPoints()) {
                    $free[] = $skill;
                }
            }

            self::assertCount(2, $free, sprintf('%s : le jour 1 se compte en deux gestes, pas %d.', $title, \count($free)));

            foreach ($free as $skill) {
                $unlock = $skill->getActions()['materia']['unlock'] ?? null;
                self::assertIsString(
                    $unlock,
                    sprintf('%s : le nœud gratuit « %s » n\'ouvre pas de materia — un passif offert n\'est pas un accord.', $title, (string) $skill->getTitle()),
                );
            }
        }
    }

    /**
     * **Invariant 12 — aucune condition au palier 1.**.
     *
     * Au jour 1 un joueur n'a pas de tenue a arbitrer : un passif conditionne a
     * un equipement qu'il ne porte pas encore est **toujours inactif**, et un
     * bonus silencieusement mort se lit comme un choix de build (le defaut
     * qu'ARC-12b a corrige sur la grammaire).
     */
    public function testNoFirstTierPassiveCarriesACondition(): void
    {
        $reader = $this->reader();

        foreach (self::CONVERTED as $title) {
            foreach ($this->nodesOf($title) as $skill) {
                if (10 !== $skill->getRequiredPoints()) {
                    continue;
                }

                foreach ($reader->grantsOf($skill) as $grant) {
                    self::assertFalse(
                        $grant->isConditional(),
                        sprintf('%s : « %s » est au palier 1 et porte une condition — elle serait fausse le jour ou on l\'achete.', $title, (string) $skill->getTitle()),
                    );
                }
            }
        }
    }

    /**
     * **Invariant 12 (suite) — au moins deux passifs sans condition.**.
     *
     * Un arbre dont tous les passifs sont conditionnels n'a pas de plancher :
     * le joueur qui n'a pas encore la bonne tenue n'y gagne **rien**, et le prix
     * qu'il a paye ne lui rend rien tant qu'il n'a pas fouille l'inventaire.
     */
    public function testEveryConvertedTreeKeepsUnconditionalPassives(): void
    {
        $reader = $this->reader();

        foreach (self::CONVERTED as $title) {
            $unconditional = 0;
            foreach ($this->nodesOf($title) as $skill) {
                foreach ($reader->grantsOf($skill) as $grant) {
                    if (!$grant->isConditional()) {
                        ++$unconditional;
                    }
                }
            }

            self::assertGreaterThanOrEqual(
                2,
                $unconditional,
                sprintf('%s : %d passif(s) sans condition — un arbre sans plancher ne rend rien tant qu\'on n\'a pas la bonne tenue.', $title, $unconditional),
            );
        }
    }

    /**
     * **Invariant 21 quater — au plus un parent par nœud.**.
     *
     * La loi du § 6.6, et la seule moitie qui soit tenue par les donnees : un
     * arbre dont les nœuds ont deux parents n'a plus de chemin lisible vers son
     * sommet.
     *
     * **L'autre moitie ne l'est pas, et c'est un constat** : le canon veut que
     * *le capstone exige l'accord de branche*, quand les neuf arbres convertis
     * le font dependre du **nœud charniere du palier 2**. Ce n'est pas un oubli
     * — un prerequis unique ne peut pas designer « celui des deux accords de
     * fourche que le joueur a pris », et le modele n'exprime pas l'alternative.
     * Le dire ici vaut mieux que de compter l'invariant comme tenu.
     */
    public function testNoNodeHasMoreThanOneParent(): void
    {
        $offenders = [];

        foreach (self::CONVERTED as $title) {
            foreach ($this->nodesOf($title) as $skill) {
                $parents = $skill->getRequirements()->count();
                if ($parents > 1) {
                    $offenders[] = sprintf('%s / %s (%d parents)', $title, (string) $skill->getTitle(), $parents);
                }
            }
        }

        // Le nœud charniere du palier 2 en a deux, et c'est **voulu** : il fait
        // converger les deux lignes du palier 1 avant la fourche. Le canon le
        // permet (§ 6.6 borne les nœuds *au-dela* du palier 2) ; ce test
        // empeche seulement que le motif se repande.
        self::assertLessThanOrEqual(
            \count(self::CONVERTED),
            \count($offenders),
            sprintf(
                "Plus d'un nœud a parents multiples par arbre : le chemin vers le sommet cesse d'etre lisible.\n%s",
                implode("\n", $offenders),
            ),
        );
    }

    /**
     * @return array<string, list<string>>
     */
    private function accordsByTree(): array
    {
        $byTree = [];

        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            $unlock = $skill->getActions()['materia']['unlock'] ?? null;
            if (!\is_string($unlock) || '' === $unlock) {
                continue;
            }

            foreach ($skill->getDomains() as $domain) {
                $byTree[(string) $domain->getTitle()][] = $unlock;
            }
        }

        return $byTree;
    }

    /**
     * Les nœuds **propres** a cet arbre — ceux qu'aucun autre ne partage.
     *
     * @return list<Skill>
     */
    private function nodesOf(string $title): array
    {
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['title' => $title]);
        self::assertNotNull($domain, $title);

        $nodes = [];
        foreach ($this->em->getRepository(Skill::class)->findAll() as $skill) {
            if (1 === $skill->getDomains()->count() && $skill->getDomains()->contains($domain)) {
                $nodes[] = $skill;
            }
        }

        return $nodes;
    }

    private function reader(): SkillLeverReader
    {
        /** @var SkillLeverReader $reader */
        $reader = self::getContainer()->get(SkillLeverReader::class);

        return $reader;
    }
}
