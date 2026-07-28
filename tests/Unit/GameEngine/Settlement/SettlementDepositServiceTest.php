<?php

namespace App\Tests\Unit\GameEngine\Settlement;

use App\Entity\App\Player;
use App\Entity\App\Settlement;
use App\Entity\App\SettlementContribution;
use App\Entity\App\Zone;
use App\Enum\SettlementIndex;
use App\Enum\SettlementRank;
use App\GameEngine\Settlement\SedimentRule;
use App\GameEngine\Settlement\SettlementDefinitionLoader;
use App\GameEngine\Settlement\SettlementDepositService;
use App\Repository\SettlementContributionRepository;
use App\Repository\SettlementRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Le depot de sediment (FOY-02).
 *
 * Trois regles valent d'etre tenues par un test, parce qu'elles cassent en
 * silence : le grind ne doit jamais battre la regularite, le grain
 * fractionnaire de la traversee ne doit jamais s'evaporer a l'arrondi, et une
 * zone sans foyer doit refuser **sans erreur** — sinon jouer a Lumiere leverait
 * une exception a chaque action.
 */
class SettlementDepositServiceTest extends TestCase
{
    private const TODAY = '2026-07-28 14:00:00';

    private Zone $zone;
    private ?Settlement $settlement = null;
    private ?SettlementContribution $contribution = null;

    protected function setUp(): void
    {
        $this->zone = new Zone();
        $this->settlement = new Settlement($this->zone);
        $this->contribution = null;
    }

    public function testAKillFeedsWarAndNothingElse(): void
    {
        $deposited = $this->service()->deposit($this->player(), 'mob_kill', null, $this->now());

        self::assertSame(1, $deposited);
        self::assertSame(1, $this->settlement->getSediment(SettlementIndex::War));
        self::assertSame(0, $this->settlement->getSediment(SettlementIndex::Trade));
        self::assertSame(1, $this->settlement->getTotalSediment());
    }

    public function testAQuestFeedsLoreByItsFullWeight(): void
    {
        $deposited = $this->service()->deposit($this->player(), 'quest', null, $this->now());

        self::assertSame(5, $deposited);
        self::assertSame(5, $this->settlement->getSediment(SettlementIndex::Lore));
    }

    public function testHarvestAndCraftBothFeedTrade(): void
    {
        $service = $this->service();
        $player = $this->player();

        $service->deposit($player, 'harvest', null, $this->now());
        $service->deposit($player, 'craft', null, $this->now());

        self::assertSame(2, $this->settlement->getSediment(SettlementIndex::Trade));
    }

    /**
     * Le coeur du jalon. La traversee vaut 0,2 grain reparti sur quatre indices,
     * soit 0,05 chacun : arrondie a chaque evenement, elle vaudrait zero et la
     * ligne du tableau serait morte sans que rien ne le dise.
     *
     * Vingt traversees dans la journee valent les quatre grains annonces par
     * BALANCE § 23.1 — c'est ce qui fait vivre une zone de transit sans qu'on y
     * farme (GAME_WORLD § 5.5, levier 4).
     */
    public function testTwentyCrossingsAreWorthTheFourGrainsPromised(): void
    {
        $service = $this->service();
        $player = $this->player();

        for ($i = 0; $i < 19; ++$i) {
            $service->deposit($player, 'travel', $this->zone, $this->now());
        }

        // Dix-neuf traversees : 0,95 par indice, rien de depose encore.
        self::assertSame(0, $this->settlement->getTotalSediment());

        $service->deposit($player, 'travel', $this->zone, $this->now());

        self::assertSame(4, $this->settlement->getTotalSediment());
        foreach (SettlementIndex::cases() as $index) {
            self::assertSame(1, $this->settlement->getSediment($index));
        }
    }

    /**
     * Reparti sur les quatre, le passage ne fait jamais dominer un indice :
     * une ville de transit reste une ville sans identite. Passer n'a jamais
     * fait une ville.
     */
    public function testCrossingsNeverGiveTheSettlementAnIdentity(): void
    {
        $service = $this->service();
        $player = $this->player();

        for ($i = 0; $i < 40; ++$i) {
            $service->deposit($player, 'travel', $this->zone, $this->now());
        }

        self::assertSame(8, $this->settlement->getTotalSediment());
        self::assertNull($this->settlement->getDominantIndex());
    }

    /**
     * Au-dela du seuil, chaque grain compte pour moitie.
     */
    public function testBeyondTheThresholdEachGrainIsWorthHalf(): void
    {
        $service = $this->service();
        $player = $this->player();

        // 40 kills = 40 grains, exactement le seuil.
        for ($i = 0; $i < 40; ++$i) {
            $service->deposit($player, 'mob_kill', null, $this->now());
        }
        self::assertSame(40, $this->settlement->getTotalSediment());

        // Au-dela, un kill vaut 0,5 : il faut en faire deux pour un grain.
        self::assertSame(0, $service->deposit($player, 'mob_kill', null, $this->now()));
        self::assertSame(1, $service->deposit($player, 'mob_kill', null, $this->now()));
        self::assertSame(41, $this->settlement->getTotalSediment());
    }

    /**
     * Le plafond mord sur ce qui **depasse**, pas sur l'action entiere : un
     * joueur a 59 grains depose son dernier grain meme si l'action en valait
     * cinq. Rogner l'action complete transformerait le plafond en piege.
     */
    public function testTheCapTrimsTheOverflowNotTheWholeAction(): void
    {
        $service = $this->service();
        $player = $this->player();

        $this->contributionFor($player)->addDailyGrains($this->now(), 59);

        self::assertSame(1, $service->deposit($player, 'quest', null, $this->now()));
        self::assertSame(1, $this->settlement->getSediment(SettlementIndex::Lore));
        self::assertSame(0, $service->deposit($player, 'quest', null, $this->now()));
    }

    public function testNothingIsDepositedOnceTheDailyCapIsReached(): void
    {
        $service = $this->service();
        $player = $this->player();
        $this->contributionFor($player)->addDailyGrains($this->now(), 60);

        self::assertSame(0, $service->deposit($player, 'quest', null, $this->now()));
        self::assertSame(0, $this->settlement->getTotalSediment());
    }

    /**
     * Le compteur journalier appartient a une date : il se remet a zero tout
     * seul. Un plafond qui dependrait d'une tache planifiee serait un plafond
     * qui saute la nuit ou la tache ne tourne pas.
     */
    public function testTheDailyCounterResetsByItselfOnTheNextDay(): void
    {
        $service = $this->service();
        $player = $this->player();
        $this->contributionFor($player)->addDailyGrains($this->now(), 60);

        $tomorrow = $this->now()->modify('+1 day');

        self::assertSame(5, $service->deposit($player, 'quest', null, $tomorrow));
    }

    /**
     * Lumiere et les Jardins sont batis sur la Voute : y jouer est normal, y
     * deposer ne l'est pas. Le refus est un zero, jamais une exception — sinon
     * chaque action jouee dans le Sanctuaire ferait une erreur.
     */
    public function testAZoneWithoutSettlementSilentlyAccumulatesNothing(): void
    {
        $this->settlement = null;

        self::assertSame(0, $this->service()->deposit($this->player(), 'mob_kill', new Zone(), $this->now()));
    }

    public function testAPlayerWithoutAZoneDepositsNothing(): void
    {
        $player = new Player();

        self::assertSame(0, $this->service()->deposit($player, 'mob_kill', null, $this->now()));
    }

    public function testAnUnknownActionDepositsNothing(): void
    {
        self::assertSame(0, $this->service()->deposit($this->player(), 'sneezing', null, $this->now()));
    }

    public function testTheContributionRecordsWhatThePlayerBuilt(): void
    {
        $service = $this->service();
        $player = $this->player();

        $service->deposit($player, 'quest', null, $this->now());
        $service->deposit($player, 'mob_kill', null, $this->now());

        $contribution = $this->contributionFor($player);
        self::assertSame(6, $contribution->getGrains());
        self::assertSame(6, $contribution->getDailyGrains($this->now()));
        self::assertSame(0, $contribution->getDailyGrains($this->now()->modify('+1 day')));
    }

    /**
     * FOY-10 : rebatir est deux fois plus rapide que batir. Le patrimoine,
     * c'est de la memoire, pas des murs.
     */
    public function testASettlementRebuildingCountsEveryGrainTwice(): void
    {
        $this->settlementWith(['trade' => 200], SettlementRank::Town);
        $this->settlement->setRank(SettlementRank::Camp);
        self::assertTrue($this->settlement->isRebuilding());

        $deposited = $this->service()->deposit($this->player(), 'quest', null, $this->now());

        self::assertSame(10, $deposited);
        self::assertSame(10, $this->settlement->getSediment(SettlementIndex::Lore));
    }

    /**
     * Le plafond mesure ce qu'un **joueur** a fait, pas ce que la ville a recu.
     * Compter le double contre son quota reviendrait a diviser par deux le temps
     * de jeu utile de qui aide une ville en difficulte — exactement le contraire
     * du but.
     */
    public function testTheDoubledGrainsDoNotEatThePlayersDailyQuota(): void
    {
        $this->settlementWith(['trade' => 200], SettlementRank::Town);
        $this->settlement->setRank(SettlementRank::Camp);

        $player = $this->player();
        $this->service()->deposit($player, 'quest', null, $this->now());

        self::assertSame(5, $this->contributionFor($player)->getDailyGrains($this->now()));
    }

    public function testASettlementAtItsBestGetsNoBonus(): void
    {
        $this->settlementWith(['trade' => 200], SettlementRank::Camp);
        self::assertFalse($this->settlement->isRebuilding());

        self::assertSame(5, $this->service()->deposit($this->player(), 'quest', null, $this->now()));
    }

    /**
     * @param array<string, int> $sediment
     */
    private function settlementWith(array $sediment, SettlementRank $rank): void
    {
        self::assertNotNull($this->settlement);
        $this->settlement->setRank($rank);
        foreach (SettlementIndex::cases() as $index) {
            $this->settlement->setSediment($index, $sediment[$index->value] ?? 0);
        }
    }

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::TODAY);
    }

    private function player(): Player
    {
        $player = new Player();
        $player->setCurrentZone($this->zone);

        return $player;
    }

    private function contributionFor(Player $player): SettlementContribution
    {
        if ($this->contribution === null) {
            self::assertNotNull($this->settlement);
            $this->contribution = new SettlementContribution($this->settlement, $player);
        }

        return $this->contribution;
    }

    /**
     * RET-02b : le multiplicateur porte sur la ligne de la table, pas sur une
     * valeur recopiee ailleurs. C'est ce qui garde le chiffrage a un seul
     * endroit quand le Tribut triple un depot.
     */
    public function testADepositWithoutAMultiplierIsTheTableLine(): void
    {
        self::assertSame(24, $this->service()->deposit($this->player(), 'commission', $this->zone, $this->now()));
    }

    public function testAMultiplierScalesTheTableLine(): void
    {
        self::assertSame(72, $this->service()->deposit($this->player(), 'commission', $this->zone, $this->now(), 3.0));
    }

    /**
     * Une action declaree hors plafond echappe au garde-fou journalier **et** ne
     * consomme pas le quota du jour. Le plafond existe pour que le grind ne batte
     * pas la regularite ; une livraison hebdomadaire n'est pas grindable, et la
     * plafonner mangerait en silence le rendez-vous d'un joueur qui a beaucoup
     * joue le meme jour. Compter son effort dans le quota reviendrait a la
     * plafonner par la bande.
     */
    public function testAnUncappedDepositIgnoresTheDailyCapAndDoesNotConsumeIt(): void
    {
        $player = $this->player();
        $service = $this->service();

        // Le joueur a deja epuise son quota du jour.
        for ($i = 0; $i < 20; ++$i) {
            $service->deposit($player, 'quest', $this->zone, $this->now());
        }
        self::assertSame(0, $service->deposit($player, 'quest', $this->zone, $this->now()));

        // La livraison passe quand meme, entiere.
        self::assertSame(24, $service->deposit($player, 'commission', $this->zone, $this->now()));

        // Et elle n'a rien consomme : une seconde livraison passe aussi.
        self::assertSame(24, $service->deposit($player, 'commission', $this->zone, $this->now()));
    }

    private function service(): SettlementDepositService
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $settlementRepository = $this->createMock(SettlementRepository::class);
        $settlementRepository->method('findOneByZone')->willReturnCallback(
            fn (Zone $zone): ?Settlement => $zone === $this->zone ? $this->settlement : null,
        );

        $contributionRepository = $this->createMock(SettlementContributionRepository::class);
        $contributionRepository->method('findOneFor')->willReturnCallback(
            fn (Settlement $settlement, Player $player): ?SettlementContribution => $this->contribution,
        );
        $entityManager->method('persist')->willReturnCallback(function (object $entity): void {
            if ($entity instanceof SettlementContribution) {
                $this->contribution = $entity;
            }
        });

        $loader = $this->createMock(SettlementDefinitionLoader::class);
        $loader->method('load')->willReturn([
            'ranks' => ['camp' => 150, 'hamlet' => 1200, 'town' => 8000, 'city' => 25000, 'metropolis' => 60000],
            'decay_rate' => 0.02,
            'dominance_margin' => 0.25,
            'sustain_days' => 28,
            'minimum_type_rank' => SettlementRank::Hamlet,
            'sediment' => [
                'mob_kill' => new SedimentRule('mob_kill', SettlementIndex::War, 1.0),
                'harvest' => new SedimentRule('harvest', SettlementIndex::Trade, 1.0),
                'craft' => new SedimentRule('craft', SettlementIndex::Trade, 1.0),
                'quest' => new SedimentRule('quest', SettlementIndex::Lore, 5.0),
                'travel' => new SedimentRule('travel', null, 0.2),
                // RET-02b : la livraison d'une commission, hors plafond.
                'commission' => new SedimentRule('commission', null, 24.0, false),
            ],
            'daily_cap_per_player' => 60,
            'diminishing_threshold' => 40,
            'diminishing_factor' => 0.5,
            'grace_days' => 28,
            'rebuild_multiplier' => 2,
            'seed' => [],
            'without_settlement' => [],
        ]);

        return new SettlementDepositService($entityManager, $settlementRepository, $contributionRepository, $loader);
    }
}
