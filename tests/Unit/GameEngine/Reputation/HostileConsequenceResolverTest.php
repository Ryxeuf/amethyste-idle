<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\App\PlayerFaction;
use App\Entity\App\Settlement;
use App\Entity\App\Zone;
use App\Entity\Game\Faction;
use App\Enum\SettlementType;
use App\GameEngine\Reputation\HostileConsequenceCatalog;
use App\GameEngine\Reputation\HostileConsequenceResolver;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

/**
 * Qui paie quoi, et surtout qui ne paie rien (FAC-03).
 *
 * Le catalogue dit ce qu'une maison fait payer ; ce test verifie que le
 * resolveur ne le fait payer qu'au bon joueur, au bon endroit — et jamais par
 * defaut : pas de ligne de reputation, pas d'hostilite ; pas de foyer Bastion,
 * pas de fouilles ; faction pas semee, crochet inerte.
 */
class HostileConsequenceResolverTest extends TestCase
{
    public function testAHostileMerchantCustomerPaysTheSurcharge(): void
    {
        $marchands = $this->faction('marchands');
        $player = new Player();

        $resolver = $this->resolver([$marchands], [$this->playerFaction($player, $marchands, -1)]);

        self::assertSame(10, $resolver->shopSurchargePercent($player));
    }

    /**
     * Hostile commence sous zero, pas a zero : un inconnu n'est pas un ennemi.
     */
    public function testANonNegativeReputationPaysNothing(): void
    {
        $marchands = $this->faction('marchands');
        $player = new Player();

        $resolver = $this->resolver([$marchands], [$this->playerFaction($player, $marchands, 0)]);

        self::assertSame(0, $resolver->shopSurchargePercent($player));
    }

    /**
     * Jamais Hostile par defaut : un joueur sans ligne de reputation chez les
     * Marchands ne leur doit rien — l'hostilite se gagne par le geste oppose,
     * elle ne s'herite pas.
     */
    public function testAPlayerWithoutAReputationLinePaysNothing(): void
    {
        $player = new Player();

        $resolver = $this->resolver([$this->faction('marchands')], []);

        self::assertSame(0, $resolver->shopSurchargePercent($player));
    }

    public function testTravelSurchargeBitesOnlyTowardABastion(): void
    {
        $chevaliers = $this->faction('chevaliers');
        $player = new Player();
        $lines = [$this->playerFaction($player, $chevaliers, -500)];

        $bastion = new Zone();
        $trading = new Zone();
        $wild = new Zone();

        $resolver = $this->resolver([$chevaliers], $lines, [
            [$bastion, SettlementType::Bastion],
            [$trading, SettlementType::Trading],
            [$wild, null],
        ]);

        self::assertSame(50, $resolver->travelSurchargePercent($player, $bastion), 'Les fouilles mordent a l\'entree d\'un Bastion.');
        self::assertSame(0, $resolver->travelSurchargePercent($player, $trading), 'Un foyer marchand ne fouille pas pour l\'Ordre.');
        self::assertSame(0, $resolver->travelSurchargePercent($player, $wild), 'Une zone sans foyer ne fouille personne.');
    }

    public function testAFriendOfTheOrderIsNeverSearched(): void
    {
        $chevaliers = $this->faction('chevaliers');
        $player = new Player();

        $bastion = new Zone();
        $resolver = $this->resolver([$chevaliers], [$this->playerFaction($player, $chevaliers, 3000)], [
            [$bastion, SettlementType::Bastion],
        ]);

        self::assertSame(0, $resolver->travelSurchargePercent($player, $bastion));
    }

    /**
     * Le crochet vers une faction pas encore semee est inerte : sans la
     * Fonderie en base, personne ne lui est Hostile — et le jour ou elle
     * arrive, la consequence declaree mord sans qu'on revienne ici.
     */
    public function testAnUnseededFactionMakesNoOneHostile(): void
    {
        $player = new Player();

        $resolver = $this->resolver([], []);

        self::assertFalse($resolver->isHostileToward($player, 'fonderie'));
        self::assertFalse($resolver->isCrystalBuybackClosed($player));
    }

    /**
     * FAC-06 : les rumeurs ne sont empoisonnees que pour les Hostiles de la
     * Confrerie — le crochet poisoned_rumors de FAC-03 prend vie.
     */
    public function testRumorsArePoisonedOnlyForBrotherhoodHostiles(): void
    {
        $ombres = $this->faction('ombres');
        $hostile = new Player();
        $neutral = new Player();

        $resolver = $this->resolver([$ombres], [$this->playerFaction($hostile, $ombres, -10)]);

        self::assertTrue($resolver->areRumorsPoisoned($hostile));
        self::assertFalse($resolver->areRumorsPoisoned($neutral), 'A un client en regle, jamais le mensonge.');
    }

    /**
     * FAC-04b : le Cercle refuse de lire pour ses Hostiles, et pour eux
     * seuls — le crochet materia_reading_refused de FAC-03 prend vie.
     */
    public function testReadingIsRefusedOnlyToCircleHostiles(): void
    {
        $mages = $this->faction('mages');
        $hostile = new Player();
        $neutral = new Player();

        $resolver = $this->resolver([$mages], [$this->playerFaction($hostile, $mages, -50)]);

        self::assertTrue($resolver->isMateriaReadingRefused($hostile));
        self::assertFalse($resolver->isMateriaReadingRefused($neutral));
    }

    /**
     * FAC-04a : la Fonderie est semee, le crochet buyback_floor_closed prend
     * vie — le plancher se ferme aux Hostiles, et a eux seuls.
     */
    public function testTheBuybackFloorClosesOnlyForFoundryHostiles(): void
    {
        $fonderie = $this->faction('fonderie');
        $hostile = new Player();
        $neutral = new Player();

        $resolver = $this->resolver([$fonderie], [$this->playerFaction($hostile, $fonderie, -200)]);

        self::assertTrue($resolver->isCrystalBuybackClosed($hostile));
        self::assertFalse($resolver->isCrystalBuybackClosed($neutral), 'Sans ligne de reputation negative, le plancher reste ouvert.');
    }

    // =====================================================================
    // Fabrique
    // =====================================================================

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
     * @param list<Faction>                                $factions    les factions semees
     * @param list<PlayerFaction>                          $lines       les lignes de reputation du joueur
     * @param list<array{0: Zone, 1: SettlementType|null}> $settlements le foyer de chaque zone (null = sans type)
     */
    private function resolver(array $factions, array $lines, array $settlements = []): HostileConsequenceResolver
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
            function (array $criteria) use ($lines): ?PlayerFaction {
                foreach ($lines as $line) {
                    if ($line->getFaction() === ($criteria['faction'] ?? null)
                        && $line->getPlayer() === ($criteria['player'] ?? null)) {
                        return $line;
                    }
                }

                return null;
            },
        );

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->method('getRepository')->willReturnCallback(
            fn (string $class): EntityRepository => $class === Faction::class ? $factionRepository : $playerFactionRepository,
        );

        $settlementRepository = $this->createMock(SettlementRepository::class);
        $settlementRepository->method('findOneByZone')->willReturnCallback(
            function (Zone $zone) use ($settlements): ?Settlement {
                foreach ($settlements as [$candidate, $type]) {
                    if ($candidate === $zone) {
                        $settlement = $this->createMock(Settlement::class);
                        $settlement->method('getType')->willReturn($type);

                        return $settlement;
                    }
                }

                return null;
            },
        );

        return new HostileConsequenceResolver(
            $entityManager,
            new HostileConsequenceCatalog(\dirname(__DIR__, 4)),
            $settlementRepository,
        );
    }
}
