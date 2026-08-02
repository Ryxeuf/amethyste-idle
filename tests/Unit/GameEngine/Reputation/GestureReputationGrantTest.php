<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\GameEngine\Reputation\FactionTensionCatalog;
use App\GameEngine\Reputation\GestureReputationCatalog;
use App\GameEngine\Reputation\ReputationManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * Les gestes nourrissent — jusqu'au plafond du jour (FAC-02).
 *
 * `ReputationTensionTest` verifie la decote ; celui-ci verifie le chemin des
 * gestes : le routage atteint la bonne faction, le plafond rogne puis ferme,
 * le lendemain rouvre, et un crochet vers une faction pas encore semee reste
 * inerte — sans rien casser et sans rien ecrire.
 */
class GestureReputationGrantTest extends TestCase
{
    public function testAGestureFeedsTheRoutedFaction(): void
    {
        $marchands = $this->faction('marchands');
        $player = new Player();
        $line = $this->playerFaction($player, $marchands, 100);

        $result = $this->manager([$marchands], [$line])
            ->grantGestureReputation($player, 'auction_sale');

        self::assertSame($line, $result);
        self::assertSame(110, $line->getReputation(), 'La vente HV doit nourrir les Marchands du montant de sa route.');
    }

    /**
     * Le plafond rogne le gain a hauteur du reste disponible, puis laisse a
     * zero : le geste n'est jamais refuse, il cesse simplement de nourrir.
     */
    public function testTheDailyCapTrimsThenCloses(): void
    {
        $chevaliers = $this->faction('chevaliers');
        $player = new Player();
        $line = $this->playerFaction($player, $chevaliers, 0);
        $manager = $this->manager([$chevaliers], [$line]);
        $cap = $this->gestureCatalog()->dailyCap();

        $line->recordGestureGain('2026-08-02', $cap - 5);
        $line->setReputation($cap - 5);

        $manager->grantGestureReputation($player, 'undead_kill', 50);
        self::assertSame($cap, $line->getReputation(), 'Le gain doit etre rogne a hauteur du reste disponible.');

        $manager->grantGestureReputation($player, 'undead_kill', 50);
        self::assertSame($cap, $line->getReputation(), 'Une fois le plafond atteint, le geste ne nourrit plus.');
    }

    /**
     * Le compteur est journalier : une autre cle de jour repart de zero. Pas
     * de cron, pas de fenetre glissante — la cle change, le compteur meurt.
     */
    public function testANewDayReopensTheCap(): void
    {
        $chevaliers = $this->faction('chevaliers');
        $player = new Player();
        $line = $this->playerFaction($player, $chevaliers, 500);
        $manager = $this->manager([$chevaliers], [$line]);

        $line->recordGestureGain('2026-08-01', $this->gestureCatalog()->dailyCap());

        $manager->grantGestureReputation($player, 'undead_kill', 25);

        self::assertSame(525, $line->getReputation(), 'Le plafond d\'hier ne doit pas fermer aujourd\'hui.');
    }

    /**
     * Le crochet vers une faction pas encore semee est inerte, pas casse : la
     * Fonderie arrive avec FAC-04, et d'ici la fondre ne nourrit rien — sans
     * erreur et sans ligne fantome.
     */
    public function testAHookTowardAnUnseededFactionIsInert(): void
    {
        $player = new Player();
        $manager = $this->manager([], []);

        self::assertNull($manager->grantGestureReputation($player, 'materia_melt'));
    }

    public function testAnUnknownGestureFeedsNothing(): void
    {
        $player = new Player();
        $manager = $this->manager([$this->faction('marchands')], []);

        self::assertNull($manager->grantGestureReputation($player, 'not_a_gesture'));
    }

    /**
     * Le chemin plafonne applique la tension comme l'autre : un geste au-dela
     * d'Ami retire chez l'oppose. Le plafond borne le gain, pas la doctrine.
     */
    public function testTheCappedPathStillAppliesTension(): void
    {
        $chevaliers = $this->faction('chevaliers');
        $ombres = $this->faction('ombres');
        $player = new Player();
        $mine = $this->playerFaction($player, $chevaliers, 6000);
        $theirs = $this->playerFaction($player, $ombres, 6000);

        $this->manager([$chevaliers, $ombres], [$mine, $theirs])
            ->grantCappedReputation($player, $chevaliers, 100);

        self::assertSame(6100, $mine->getReputation());
        self::assertSame(5950, $theirs->getReputation(), 'La moitie du gain au-dela d\'Ami doit etre retiree a l\'oppose.');
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function gestureCatalog(): GestureReputationCatalog
    {
        return new GestureReputationCatalog(\dirname(__DIR__, 4));
    }

    private function faction(string $slug): Faction
    {
        return (new Faction())->setSlug($slug)->setName($slug);
    }

    private function playerFaction(Player $player, Faction $faction, int $reputation): PlayerFaction
    {
        $playerFaction = new PlayerFaction();
        $playerFaction->setPlayer($player);
        $playerFaction->setFaction($faction);
        $playerFaction->setReputation($reputation);

        return $playerFaction;
    }

    /**
     * @param list<Faction>       $factions       les factions semees
     * @param list<PlayerFaction> $playerFactions les lignes de reputation du joueur
     */
    private function manager(array $factions, array $playerFactions): ReputationManager
    {
        $factionRepository = $this->createMock(EntityRepository::class);
        $factionRepository->method('findOneBy')->willReturnCallback(
            function (array $criteria) use ($factions): ?Faction {
                foreach ($factions as $faction) {
                    if ($faction->getSlug() === ($criteria['slug'] ?? null)) {
                        return $faction;
                    }
                }

                return null;
            },
        );

        $playerFactionRepository = $this->createMock(EntityRepository::class);
        $playerFactionRepository->method('findOneBy')->willReturnCallback(
            function (array $criteria) use ($playerFactions): ?PlayerFaction {
                foreach ($playerFactions as $playerFaction) {
                    if ($playerFaction->getFaction() === ($criteria['faction'] ?? null)) {
                        return $playerFaction;
                    }
                }

                return null;
            },
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => $class === Faction::class ? $factionRepository : $playerFactionRepository,
        );

        // La cle du jour est fixee : le test de reouverture pose un cumul sur
        // « hier » et verifie qu'« aujourd'hui » ne le voit pas.
        return new class($entityManager, new FactionTensionCatalog(\dirname(__DIR__, 4)), $this->gestureCatalog()) extends ReputationManager {
            protected function todayKey(): string
            {
                return '2026-08-02';
            }
        };
    }
}
