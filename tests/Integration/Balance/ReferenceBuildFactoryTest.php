<?php

namespace App\Tests\Integration\Balance;

use App\Entity\Game\Domain;
use App\Enum\CombatLever;
use App\Enum\DomainRole;
use App\GameEngine\Balance\ReferenceBuildFactory;
use App\GameEngine\Progression\CombatLeverScale;
use App\GameEngine\Progression\DomainRoleDefinitionLoader;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * Les builds de reference du simulateur, sur la base reelle (ARC-17c).
 *
 * GAME_ARCHETYPES § 9 sexies. Le simulateur compare les fonctions sur la meme
 * ligne ; encore faut-il des personnages, et le canon dit comment **ne pas** les
 * obtenir : *ecrits en dur, ils se perimeraient au premier changement de
 * fixture — et c'est exactement ce qu'on cherche a detecter.*
 *
 * Ce contrat verifie que la derivation tient. Il ne joue aucun combat : jouer
 * est ARC-17c-b, et un instrument qui mesurerait avec des builds faux serait
 * pire qu'un instrument absent.
 */
class ReferenceBuildFactoryTest extends AbstractIntegrationTestCase
{
    /**
     * Les cases de la grille que les arbres livres couvrent aujourd'hui.
     *
     * **Cliquet, et il doit grandir** : les cinq arbres au gabarit (ARC-07a→d,
     * ARC-08a) tiennent quatre fonctions et trois registres, mais **pas les
     * douze cases**. Un simulateur qui tairait ce qu'il ne joue pas donnerait a
     * ses moyennes une autorite qu'elles n'ont pas — d'ou cette liste, nommee
     * plutot que sous-entendue. ARC-08 la remplit.
     *
     * @var list<string>
     */
    private const COVERED_CELLS = [
        // ARC-08b — l'Assassin remplit `assault x melee`, la case que les
        // quatre patrons laissaient vide : l'assaut n'existait qu'en sorts et
        // au tir, jamais au contact.
        'assault x melee',
        'assault x ranged',
        'assault x spell',
        'bulwark x melee',
        // ARC-08c — l'Artificier remplit `control x ranged`.
        'control x ranged',
        'control x spell',
        'upkeep x spell',
    ];

    /**
     * **Chaque build depense exactement le budget de l'arbre.**.
     *
     * C'est l'invariant qui rend le simulateur credible : deux builds qui ne
     * porteraient pas le meme budget ne se compareraient pas, et l'ecart mesure
     * dirait le budget plutot que la fonction.
     */
    public function testEveryBuildCarriesTheTreeBudget(): void
    {
        $budget = (new DomainRoleDefinitionLoader(\dirname(__DIR__, 3)))->load()['budget']['total'];
        $builds = $this->factory()->all();

        self::assertNotEmpty($builds, 'Aucun build : le simulateur n\'aurait personne a jouer.');

        foreach ($builds as $build) {
            self::assertSame(
                $budget,
                $build->totalBudget(),
                sprintf('%s depense %d pb pour un budget de %d.', $build->label(), $build->totalBudget(), $budget),
            );
        }
    }

    /**
     * Aucun build ne depasse un plafond de levier.
     *
     * Le plafond est **par arbre**, donc il se lit sur le total d'une branche et
     * jamais sur un nœud : trois nœuds a 7 pb sur `power` passeraient un a un et
     * casseraient la borne ensemble.
     */
    public function testNoBuildExceedsALeverCap(): void
    {
        /** @var CombatLeverScale $scale */
        $scale = self::getContainer()->get(CombatLeverScale::class);

        foreach ($this->factory()->all() as $build) {
            foreach ($build->leverBudget as $lever => $points) {
                $cap = $scale->capOf(CombatLever::from($lever));
                self::assertLessThanOrEqual(
                    $cap,
                    $points,
                    sprintf('%s : %s depense %d pb pour un plafond de %d.', $build->label(), $lever, $points, $cap),
                );
            }
        }
    }

    /**
     * **Une branche fait un build, pas un arbre.**.
     *
     * La fourche existe pour que deux personnages du meme arbre ne soient pas le
     * meme personnage (ARC-14). S'ils portaient les memes leviers, le simulateur
     * mesurerait deux fois la meme chose en croyant en mesurer deux.
     */
    public function testTheTwoBranchesOfATreeAreTwoDifferentBuilds(): void
    {
        $byTree = [];
        foreach ($this->factory()->all() as $build) {
            $byTree[$build->treeKey][$build->branch] = $build->leverBudget;
        }

        foreach ($byTree as $tree => $branches) {
            self::assertCount(2, $branches, sprintf('%s : une fourche a deux branches.', $tree));

            $spends = array_values($branches);
            self::assertNotSame(
                $spends[0],
                $spends[1],
                sprintf('%s : les deux branches portent les memes leviers — ce n\'est pas une fourche.', $tree),
            );
        }
    }

    /**
     * Chaque build ouvre au moins un geste, et sa branche en ouvre un a elle.
     *
     * Un build sans geste ne peut pas jouer un tour ; une branche sans geste
     * propre est une decoration (§ 6.1 bis, regle 5).
     */
    public function testEveryBuildOpensGesturesAndItsBranchOpensOneOfItsOwn(): void
    {
        $factory = $this->factory();

        foreach ($factory->all() as $build) {
            self::assertNotEmpty($build->accords, sprintf('%s n\'ouvre aucun geste.', $build->label()));

            $domain = $this->domainOf($build->domainTitle);
            self::assertNotEmpty(
                $factory->branchAccordsOf($domain, $build->branch),
                sprintf('%s : la branche n\'ouvre aucun geste en propre.', $build->label()),
            );
        }
    }

    /**
     * **Le simulateur dit ce qu'il ne joue pas.**.
     *
     * Les cinq arbres au gabarit ne remplissent pas les douze cases de la grille
     * fonction x registre. Tant que c'est vrai, un seuil calcule sur ces builds
     * ne vaut que pour les cases listees — et le dire est la condition pour que
     * les mesures d'ARC-17c-b soient lisibles.
     */
    public function testTheCoverageIsNamedRatherThanAssumed(): void
    {
        self::assertSame(
            self::COVERED_CELLS,
            $this->factory()->coverage(),
            'La couverture de la grille a bouge. Elle peut grandir — c\'est le travail d\'ARC-08.',
        );
    }

    /**
     * Les quatre fonctions sont jouables — sans quoi l'ancre de fonction
     * n'aurait rien a comparer.
     *
     * C'est la raison pour laquelle ARC-08a a livre le Necromancien avant ce
     * jalon : les quatre patrons couvraient l'assaut **deux fois** et laissaient
     * le controle vide, si bien que le seuil « aucune fonction dominante dans
     * les deux colonnes » n'aurait pas pu se calculer.
     */
    public function testTheFourFunctionsAreAllPlayable(): void
    {
        $roles = [];
        foreach ($this->factory()->all() as $build) {
            $roles[$build->role->value] = true;
        }

        foreach (DomainRole::cases() as $role) {
            self::assertArrayHasKey(
                $role->value,
                $roles,
                sprintf('Aucun build de fonction « %s » : l\'ancre de fonction ne pourrait pas se calculer.', $role->value),
            );
        }
    }

    private function factory(): ReferenceBuildFactory
    {
        /** @var ReferenceBuildFactory $factory */
        $factory = self::getContainer()->get(ReferenceBuildFactory::class);

        return $factory;
    }

    private function domainOf(string $title): Domain
    {
        $domain = $this->em->getRepository(Domain::class)->findOneBy(['title' => $title]);
        self::assertNotNull($domain, $title);

        return $domain;
    }
}
