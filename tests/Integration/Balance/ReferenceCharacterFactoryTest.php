<?php

namespace App\Tests\Integration\Balance;

use App\Enum\CombatLever;
use App\Enum\CombatRegister;
use App\Enum\MonsterRank;
use App\GameEngine\Balance\EncounterSimulator;
use App\GameEngine\Balance\ReferenceBuildFactory;
use App\GameEngine\Balance\ReferenceCharacterFactory;
use App\GameEngine\Balance\VitalityLaw;
use App\GameEngine\Progression\CombatLeverScale;
use App\Service\PlayerFactory;
use App\Tests\Integration\AbstractIntegrationTestCase;

/**
 * La fiche de personnage derivee des vraies donnees (ARC-17c-b).
 *
 * `ReferenceBuildFactoryTest` verifie ce qu'une branche **apprend** ; celui-ci
 * verifie ce que ca **vaut**, et que la conversion passe bien par le
 * convertisseur unique d'ARC-03. Il ne pose aucun seuil d'equilibrage : les
 * seuils sont ARC-17c-c, et les poser avant que le simulateur joue une journee
 * reviendrait a figer une mesure a moitie faite.
 */
class ReferenceCharacterFactoryTest extends AbstractIntegrationTestCase
{
    /**
     * Tout build produit quelqu'un qui peut entrer en rencontre.
     *
     * Une barre de vie nulle ou un geste absent ne se lisent pas comme un
     * mauvais equilibrage : ils se lisent comme un instrument casse, et une
     * table produite dessus serait pire qu'absente.
     */
    public function testEveryBuildProducesSomeoneWhoCanFight(): void
    {
        $characters = $this->characters();

        self::assertNotEmpty($characters, 'Aucune fiche : le simulateur n\'aurait personne a jouer.');

        foreach ($characters as $character) {
            self::assertGreaterThan(0, $character->maxLife, sprintf('%s entre en rencontre sans barre de vie.', $character->label));
            self::assertGreaterThan(
                0.0,
                max($character->expectedDamagePerTurn(), $character->expectedFallbackDamagePerTurn()),
                sprintf('%s ne peut retirer aucun point de vie : il ne conclurait jamais une rencontre.', $character->label),
            );
        }
    }

    /**
     * **La barre de vie part de la base du jeu, jamais d'un chiffre choisi.**.
     *
     * Sans niveau global (regle absolue n° 6), une barre ne grandit que par
     * l'arbre : la fiche doit donc valoir la base de `PlayerFactory` multipliee
     * par le seul levier `life`. Recopier une valeur ici la ferait diverger de
     * son original a la premiere fois qu'on deplace l'une des deux.
     */
    public function testTheLifeBarIsTheGameBaseTimesItsLever(): void
    {
        /** @var CombatLeverScale $scale */
        $scale = self::getContainer()->get(CombatLeverScale::class);
        $buildFactory = $this->buildFactory();
        $characterFactory = $this->characterFactory();

        foreach ($buildFactory->all() as $build) {
            $points = $build->leverBudget[CombatLever::Life->value] ?? 0;
            // ARC-20c — la barre part de **son palier** et non plus d'une base
            // unique. Elle valait `PlayerFactory::BASE_LIFE`, c'est-a-dire
            // 20 PV a tous les paliers, quand une elite de palier 4 en retire
            // 110 : *un simulateur qui mesure une barre fausse mesure faux, et
            // ses moyennes ont l'air justes*.
            $expected = (int) round(VitalityLaw::barFor(VitalityLaw::FIRST_TIER) * (1.0 + $scale->effectOf(CombatLever::Life, $points) / 100.0));

            self::assertSame(
                max(1, $expected),
                $characterFactory->of($build)->maxLife,
                sprintf('%s : la barre de vie ne suit pas le convertisseur.', $build->label()),
            );
        }
    }

    /**
     * **Seul le registre des sorts porte une ressource d'une rencontre a
     * l'autre.**.
     *
     * C'est la lecture de `DailyAnchor::carriesOverBetweenEncounters` : la melee
     * paie en tours, le tir dans son carquois, tous deux **dans** la rencontre.
     * Donner un pool a l'un des deux ici ferait payer deux fois un cout qu'ils
     * paient ailleurs.
     */
    public function testOnlySpellsCarryAResourceBetweenEncounters(): void
    {
        foreach ($this->characters() as $character) {
            if ($character->register === CombatRegister::Spell) {
                self::assertGreaterThan(0, $character->maxResource, sprintf('%s lance des sorts sans pool.', $character->label));
                continue;
            }

            self::assertSame(0, $character->maxResource, sprintf('%s porte un pool que son registre n\'a pas.', $character->label));
            self::assertSame(0, $character->gestureCost, sprintf('%s paie une ressource que son registre n\'a pas.', $character->label));
        }
    }

    /**
     * Le simulateur joue les vraies fiches sans jamais rendre de mesure absurde.
     *
     * On ne juge pas ici *si* un build s'en sort — c'est le sujet d'ARC-17c-c —
     * mais que la boucle rend bien des tours bornes, un cout de vie qui ne
     * depasse pas la barre et une ressource qui ne depasse pas le pool. Un
     * instrument qui rendrait 110 % d'un pool ne serait pas severe, il serait
     * faux.
     */
    public function testThePlayedEncountersStayWithinTheirOwnUnits(): void
    {
        $simulator = new EncounterSimulator();

        foreach ($this->characters() as $character) {
            foreach ([MonsterRank::Common, MonsterRank::Elite, MonsterRank::Boss] as $rank) {
                $outcome = $simulator->simulate($character, 2, $rank);

                self::assertGreaterThan(0, $outcome->turns, sprintf('%s : une rencontre sans tour.', $character->label));
                self::assertLessThanOrEqual(EncounterSimulator::MAX_TURNS, $outcome->turns);
                self::assertLessThanOrEqual($character->maxLife, $outcome->lifeLost, sprintf('%s perd plus que sa barre.', $character->label));
                self::assertLessThanOrEqual($character->maxResource, $outcome->resourceSpent, sprintf('%s depense plus que son pool.', $character->label));
            }
        }
    }

    /**
     * @return list<\App\GameEngine\Balance\ReferenceCharacter>
     */
    private function characters(): array
    {
        $factory = $this->characterFactory();

        return array_map(fn ($build) => $factory->of($build), $this->buildFactory()->all());
    }

    private function buildFactory(): ReferenceBuildFactory
    {
        /** @var ReferenceBuildFactory $factory */
        $factory = self::getContainer()->get(ReferenceBuildFactory::class);

        return $factory;
    }

    private function characterFactory(): ReferenceCharacterFactory
    {
        /** @var ReferenceCharacterFactory $factory */
        $factory = self::getContainer()->get(ReferenceCharacterFactory::class);

        return $factory;
    }
}
