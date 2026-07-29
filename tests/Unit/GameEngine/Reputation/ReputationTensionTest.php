<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\Game\Faction;
use App\GameEngine\Reputation\FactionTensionCatalog;
use App\GameEngine\Reputation\ReputationManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * Ce qu'on gagne ici, on le perd la-bas — au-dela d'Ami seulement (FAC-01).
 *
 * GAME_WORLD § 6.4 a. Le test precedent (`FactionTensionCatalogTest`) verifie
 * l'arithmetique ; celui-ci verifie qu'elle atteint bien la bonne faction, et
 * qu'elle s'arrete la ou elle doit — sur une paire absente, sur une faction pas
 * encore semee, et au plancher.
 */
class ReputationTensionTest extends TestCase
{
    public function testAGainBeyondTheTierCostsTheOpponent(): void
    {
        $chevaliers = $this->faction('chevaliers');
        $ombres = $this->faction('ombres');

        $player = new Player();
        $mine = $this->playerFaction($player, $chevaliers, 6000);
        $theirs = $this->playerFaction($player, $ombres, 6000);

        $this->manager([$chevaliers, $ombres], [$mine, $theirs])
            ->addReputation($player, $chevaliers, 200);

        self::assertSame(6200, $mine->getReputation());
        self::assertSame(5900, $theirs->getReputation(), 'La moitie du gain doit etre retiree a l\'oppose.');
    }

    /**
     * En deca du palier, l'oppose ne perd rien.
     *
     * C'est ce qui permet de decouvrir les cinq maisons avant d'avoir a
     * choisir : sans cette borne, les premieres quetes de faction fermeraient
     * deja des portes.
     */
    public function testAGainBelowTheTierLeavesTheOpponentAlone(): void
    {
        $chevaliers = $this->faction('chevaliers');
        $ombres = $this->faction('ombres');

        $player = new Player();
        $mine = $this->playerFaction($player, $chevaliers, 300);
        $theirs = $this->playerFaction($player, $ombres, 900);

        $this->manager([$chevaliers, $ombres], [$mine, $theirs])
            ->addReputation($player, $chevaliers, 200);

        self::assertSame(500, $mine->getReputation());
        self::assertSame(900, $theirs->getReputation());
    }

    /**
     * La Guilde des Marchands ne coute rien a personne.
     *
     * « Elle vend aux deux, c'est son identite. » Une faction hors tension qui
     * ferait perdre chez une autre serait la fin de la seule maison ou l'on peut
     * monter sans renoncer.
     */
    public function testAFactionOutsideTheAxisCostsNothing(): void
    {
        $marchands = $this->faction('marchands');
        $ombres = $this->faction('ombres');

        $player = new Player();
        $mine = $this->playerFaction($player, $marchands, 9000);
        $theirs = $this->playerFaction($player, $ombres, 9000);

        $this->manager([$marchands, $ombres], [$mine, $theirs])
            ->addReputation($player, $marchands, 500);

        self::assertSame(9000, $theirs->getReputation());
    }

    /**
     * La decote ne cree pas de reputation la ou il n'y en a jamais eu.
     *
     * Un joueur qui n'a jamais croise la Confrerie ne lui doit rien : lui
     * inventer une ligne negative le rendrait Hostile d'une maison qu'il ne
     * connait pas.
     */
    public function testAnUnmetOpponentIsNotCreated(): void
    {
        $chevaliers = $this->faction('chevaliers');
        $ombres = $this->faction('ombres');

        $player = new Player();
        $mine = $this->playerFaction($player, $chevaliers, 9000);

        $manager = $this->manager([$chevaliers, $ombres], [$mine]);
        $manager->addReputation($player, $chevaliers, 400);

        self::assertNull($manager->getPlayerFaction($player, $ombres));
    }

    /**
     * Une paire dont un membre n'existe pas encore reste inerte.
     *
     * La Fonderie arrive avec FAC-04 : d'ici la, monter chez les Mages ne coute
     * rien, et surtout ne casse rien.
     */
    public function testAPairWhoseFactionIsNotSeededYetIsInert(): void
    {
        $mages = $this->faction('mages');

        $player = new Player();
        $mine = $this->playerFaction($player, $mages, 9000);

        $this->manager([$mages], [$mine])->addReputation($player, $mages, 400);

        self::assertSame(9400, $mine->getReputation());
    }

    /**
     * La decote s'arrete au plancher.
     *
     * « On ne peut pas renoncer a plus que ce qu'on aurait pu donner. » Sans
     * plancher, un joueur d'un an serait Hostile a une profondeur que plus aucun
     * geste ne pourrait rattraper — et la reputation cesserait d'etre une
     * echelle pour devenir une condamnation.
     */
    public function testTheOffsetStopsAtTheFloor(): void
    {
        $chevaliers = $this->faction('chevaliers');
        $ombres = $this->faction('ombres');

        $player = new Player();
        $mine = $this->playerFaction($player, $chevaliers, 50000);
        $theirs = $this->playerFaction($player, $ombres, -1900);

        $this->manager([$chevaliers, $ombres], [$mine, $theirs])
            ->addReputation($player, $chevaliers, 10000);

        self::assertSame(
            $this->catalog()->offsetFloor(),
            $theirs->getReputation(),
            'La decote doit s\'arreter au plancher, pas le traverser.',
        );
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

    private function catalog(): FactionTensionCatalog
    {
        return new FactionTensionCatalog(\dirname(__DIR__, 4));
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

        return new ReputationManager($entityManager, $this->catalog());
    }
}
