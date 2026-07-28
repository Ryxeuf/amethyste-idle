<?php

declare(strict_types=1);

namespace App\Tests\Unit\GameEngine\Player;

use App\Entity\App\CraftJob;
use App\Entity\App\CraftOrder;
use App\Entity\App\DomainExperience;
use App\Entity\App\Fight;
use App\Entity\App\GardenPlot;
use App\Entity\App\Player;
use App\Entity\App\PlayerExpedition;
use App\Entity\App\PlayerHouse;
use App\Entity\App\PlayerJournalEntry;
use App\Entity\App\PlayerQuest;
use App\Entity\App\PlayerWeeklyCommission;
use App\Entity\App\Zone;
use App\Enum\InfluenceActivityType;
use App\GameEngine\Player\HubPendingItem;
use App\GameEngine\Player\HubResume;
use App\GameEngine\Player\PlayerHubDigest;
use App\GameEngine\Quest\PlayerQuestHelper;
use App\Repository\CraftJobRepository;
use App\Repository\CraftOrderRepository;
use App\Repository\GardenPlotRepository;
use App\Repository\PlayerExpeditionRepository;
use App\Repository\PlayerHouseRepository;
use App\Repository\PlayerJournalEntryRepository;
use App\Repository\PlayerWeeklyCommissionRepository;
use App\Repository\PrivateMessageRepository;
use PHPUnit\Framework\TestCase;

/**
 * Le hub — modele de lecture du tableau de bord.
 *
 * Ce que ces tests protegent tient en trois phrases. La reprise ne propose
 * qu'une action, et l'ordre des etats est un ordre de contrainte : un mort ne
 * voyage pas. Une attente n'entre dans la liste que si le joueur peut agir
 * dessus — c'est la regle qui empeche le hub de redevenir un tableau de bord de
 * statistiques. Et le recap ne parle que des dernieres 24 h, sans quoi « depuis
 * hier » finirait par raconter le mois dernier.
 */
class PlayerHubDigestTest extends TestCase
{
    private const NOW = '2026-07-27 12:00:00';

    // =====================================================================
    // La reprise
    // =====================================================================

    /**
     * Mort d'abord : tant que le personnage est a terre, aucun autre etat n'a
     * de sens a etre propose.
     */
    public function testDeathOutranksEveryOtherState(): void
    {
        $player = $this->player();
        $player->setLife(0);
        $player->setTravelToZone($this->createMock(Zone::class));
        $player->setTravelArrivesAt($this->now()->modify('+10 minutes'));

        $resume = $this->digest()->resume($player, $this->now());

        self::assertSame(HubResume::STATE_DEAD, $resume->state);
        self::assertTrue($resume->actionable);
    }

    public function testAnOngoingFightOutranksTravel(): void
    {
        $player = $this->player();
        $player->setFight(new Fight());
        $player->setTravelToZone($this->createMock(Zone::class));
        $player->setTravelArrivesAt($this->now()->modify('+10 minutes'));

        $resume = $this->digest()->resume($player, $this->now());

        self::assertSame(HubResume::STATE_FIGHT, $resume->state);
        self::assertSame('app_game_fight', $resume->route);
    }

    /**
     * Registre 2 de GAME_ZONE_ACTIONS.md : ce qui tourne sans le joueur
     * s'affiche comme un etat, jamais comme un bouton.
     */
    public function testTravelIsAStateWithACountdownNotAnAction(): void
    {
        $player = $this->player();
        $destination = $this->createMock(Zone::class);
        $player->setTravelToZone($destination);
        $player->setTravelArrivesAt($this->now()->modify('+7 minutes'));

        $resume = $this->digest()->resume($player, $this->now());

        self::assertSame(HubResume::STATE_TRAVEL, $resume->state);
        self::assertFalse($resume->actionable);
        self::assertSame(420, $resume->remainingSeconds);
        self::assertSame($destination, $resume->zone);
    }

    public function testARunningExpeditionIsAStateAndAFinishedOneIsAnAction(): void
    {
        $zone = $this->createMock(Zone::class);
        $player = $this->player();

        $running = $this->digestWith([
            'expedition' => $this->expedition($player, $zone, '+2 hours'),
        ])->resume($player, $this->now());

        self::assertSame(HubResume::STATE_EXPEDITION, $running->state);
        self::assertFalse($running->actionable);

        $finished = $this->digestWith([
            'expedition' => $this->expedition($player, $zone, '-1 hour'),
        ])->resume($player, $this->now());

        self::assertSame(HubResume::STATE_EXPEDITION_DONE, $finished->state);
        self::assertTrue($finished->actionable);
    }

    public function testAFreePlayerIsSentBackToTheirZone(): void
    {
        $zone = $this->createMock(Zone::class);
        $player = $this->player();
        $player->setCurrentZone($zone);

        $resume = $this->digest()->resume($player, $this->now());

        self::assertSame(HubResume::STATE_READY, $resume->state);
        self::assertSame('app_game_zone', $resume->route);
        self::assertSame($zone, $resume->zone);
    }

    /**
     * Un joueur sans zone n'est pas envoye sur un ecran de zone vide : la carte
     * du monde est le seul endroit ou il peut se sortir de la.
     */
    public function testAPlayerWithoutAZoneIsSentToTheWorldMap(): void
    {
        $resume = $this->digest()->resume($this->player(), $this->now());

        self::assertSame(HubResume::STATE_LOST, $resume->state);
        self::assertSame('app_game_world_map', $resume->route);
    }

    // =====================================================================
    // Les attentes
    // =====================================================================

    public function testAnIdlePlayerHasNothingWaiting(): void
    {
        self::assertSame([], $this->digest()->pending($this->player(), $this->now()));
    }

    /**
     * L'ordre est un ordre de cout d'inaction : ce qui se degrade passe devant
     * ce qui dort en attendant, qui passe devant ce qui ne fait qu'attendre.
     */
    public function testWaitingItemsAreOrderedByCostOfDoingNothing(): void
    {
        $player = $this->player();
        $player->addDomainExperience($this->domainExperience(120, 40));
        $player->getQuests()->add($this->createMock(PlayerQuest::class));

        $house = $this->createMock(PlayerHouse::class);
        $house->method('isInArrears')->willReturn(true);

        $digest = $this->digestWith([
            'house' => $house,
            'expedition' => $this->expedition($player, $this->createMock(Zone::class), '-1 hour'),
            'unread' => 3,
            'questsCompleted' => true,
        ]);

        $keys = array_map(static fn (HubPendingItem $item): string => $item->key, $digest->pending($player, $this->now()));

        self::assertSame(
            ['house_rent', 'expedition_ready', 'quests_ready', 'talent_xp', 'messages_unread'],
            $keys,
        );
    }

    /**
     * RET-02b : une commission accomplie attend d'etre portee au foyer. C'est
     * une attente actionnable au sens de la regle 2 — le joueur doit se
     * deplacer, et sans cette ligne il ne saurait pas que sa semaine attend
     * d'etre refermee. Une commission encore en cours, elle, n'attend rien.
     */
    public function testAFinishedCommissionIsAWaitingItemAndAnUnfinishedOneIsNot(): void
    {
        $player = $this->player();

        $done = new PlayerWeeklyCommission($player, '2026-W31', 'slug', InfluenceActivityType::Quest, 2);
        $done->addProgress(2);
        self::assertSame(['commission_ready'], $this->pendingKeysWithCommission($done));

        $running = new PlayerWeeklyCommission($player, '2026-W31', 'slug', InfluenceActivityType::Quest, 2);
        $running->addProgress(1);
        self::assertSame([], $this->pendingKeysWithCommission($running));
    }

    /**
     * @return list<string>
     */
    private function pendingKeysWithCommission(PlayerWeeklyCommission $commission): array
    {
        $repository = $this->createMock(PlayerWeeklyCommissionRepository::class);
        $repository->method('findCurrent')->willReturn($commission);

        $digest = $this->digestWith(['commissionRepository' => $repository]);

        return array_map(
            static fn (HubPendingItem $item): string => $item->key,
            $digest->pending($this->player(), $this->now()),
        );
    }

    /**
     * La regle d'admission : une attente est actionnable. Un ouvrage encore sur
     * l'etabli et une commande dont le travail n'est pas fini ne le sont pas.
     */
    public function testWorkStillUnderWayIsNotAWaitingItem(): void
    {
        $craftJob = $this->createMock(CraftJob::class);
        $craftJob->method('isReady')->willReturn(false);

        $order = $this->createMock(CraftOrder::class);
        $order->method('isReady')->willReturn(false);

        $digest = $this->digestWith(['craftJob' => $craftJob, 'claimedOrders' => [$order]]);

        self::assertSame([], $digest->pending($this->player(), $this->now()));
    }

    public function testFinishedWorkAndDeliverableOrdersAreWaitingItems(): void
    {
        $craftJob = $this->createMock(CraftJob::class);
        $craftJob->method('isReady')->willReturn(true);

        $ready = $this->createMock(CraftOrder::class);
        $ready->method('isReady')->willReturn(true);
        $pending = $this->createMock(CraftOrder::class);
        $pending->method('isReady')->willReturn(false);

        $digest = $this->digestWith(['craftJob' => $craftJob, 'claimedOrders' => [$ready, $pending]]);
        $items = $digest->pending($this->player(), $this->now());

        $keys = array_map(static fn (HubPendingItem $item): string => $item->key, $items);
        self::assertSame(['craft_ready', 'craft_orders'], $keys);

        // Une seule des deux commandes est livrable : le compte doit le dire.
        self::assertSame(1, $items[1]->params['%count%']);
        self::assertSame('', $items[1]->params['%plural%']);
    }

    public function testRipePlotsAreCountedAndPluralised(): void
    {
        $house = $this->createMock(PlayerHouse::class);
        $house->method('isInArrears')->willReturn(false);

        $digest = $this->digestWith([
            'house' => $house,
            'plots' => [$this->plot(true), $this->plot(true), $this->plot(false)],
        ]);

        $items = $digest->pending($this->player(), $this->now());

        self::assertCount(1, $items);
        self::assertSame('garden_ripe', $items[0]->key);
        self::assertSame(2, $items[0]->params['%count%']);
        self::assertSame('s', $items[0]->params['%plural%']);
        self::assertSame(HubPendingItem::TONE_GAIN, $items[0]->tone);
    }

    /**
     * Un loyer en retard coute quelque chose tant qu'on ne fait rien : c'est la
     * seule ligne de perte du hub, et elle doit le dire.
     */
    public function testOverdueRentIsMarkedAsALoss(): void
    {
        $house = $this->createMock(PlayerHouse::class);
        $house->method('isInArrears')->willReturn(true);

        $items = $this->digestWith(['house' => $house])->pending($this->player(), $this->now());

        self::assertCount(1, $items);
        self::assertSame(HubPendingItem::TONE_LOSS, $items[0]->tone);
    }

    /**
     * De l'XP entierement depensee n'attend personne — la ligne disparait au
     * lieu d'afficher zero.
     */
    public function testFullySpentExperienceDoesNotWait(): void
    {
        $player = $this->player();
        $player->addDomainExperience($this->domainExperience(300, 300));

        self::assertSame([], $this->digest()->pending($player, $this->now()));
    }

    // =====================================================================
    // Le recap
    // =====================================================================

    public function testRecapAggregatesTheLastDayAndKeepsTheMostRecentLines(): void
    {
        $entries = [
            $this->journalEntry('combat_victory', '-1 hour'),
            $this->journalEntry('combat_victory', '-3 hours'),
            $this->journalEntry('gathering', '-4 hours'),
            $this->journalEntry('combat_victory', '-5 hours'),
            $this->journalEntry('craft', '-6 hours'),
            $this->journalEntry('gathering', '-7 hours'),
            $this->journalEntry('exploration', '-30 hours'),
        ];

        $recap = $this->digestWith(['journal' => $entries])->recap($this->player(), $this->now());

        // La sortie d'il y a 30 h est hors fenetre : ni comptee, ni affichee.
        self::assertSame(['combat_victory' => 3, 'gathering' => 2, 'craft' => 1], $recap->counts);
        self::assertCount(PlayerHubDigest::RECAP_ENTRIES, $recap->entries);
        self::assertSame($entries[0], $recap->entries[0]);
        self::assertFalse($recap->isEmpty());
    }

    public function testAnEmptyJournalYieldsAnEmptyRecap(): void
    {
        $recap = $this->digest()->recap($this->player(), $this->now());

        self::assertTrue($recap->isEmpty());
        self::assertSame([], $recap->counts);
    }

    // =====================================================================
    // Les quetes
    // =====================================================================

    public function testQuestsAreListedMostAdvancedFirstAndCapped(): void
    {
        $player = $this->player();
        for ($i = 0; $i < 4; ++$i) {
            $player->getQuests()->add($this->createMock(PlayerQuest::class));
        }

        $helper = $this->createMock(PlayerQuestHelper::class);
        $helper->method('getPlayerQuestProgress')->willReturnOnConsecutiveCalls(10, 90, 50, 70);

        $lines = $this->digestWith(['questHelper' => $helper])->quests($player);

        self::assertCount(3, $lines);
        self::assertSame([90, 70, 50], array_column($lines, 'percent'));
    }

    // =====================================================================
    // Fabriques
    // =====================================================================

    private function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable(self::NOW);
    }

    private function player(): Player
    {
        $player = new Player();
        $player->setName('Alba');
        $player->setLife(30);
        $player->setMaxLife(30);

        return $player;
    }

    private function expedition(Player $player, Zone $zone, string $endsAt): PlayerExpedition
    {
        return new PlayerExpedition(
            $player,
            $zone,
            'short',
            $this->now()->modify('-4 hours'),
            $this->now()->modify($endsAt),
        );
    }

    private function domainExperience(int $total, int $used): DomainExperience
    {
        $experience = new DomainExperience();
        $experience->setTotalExperience($total);
        $experience->setUsedExperience($used);

        return $experience;
    }

    private function plot(bool $ripe): GardenPlot
    {
        $plot = $this->createMock(GardenPlot::class);
        $plot->method('isRipe')->willReturn($ripe);

        return $plot;
    }

    private function journalEntry(string $type, string $createdAt): PlayerJournalEntry
    {
        $entry = new PlayerJournalEntry();
        $entry->setType($type);
        $entry->setMessage('...');

        // `createdAt` est pose par Gedmo au flush : en test unitaire il faut
        // l'ecrire soi-meme, et le trait n'expose pas toujours de setter typé
        // de la meme facon selon la version.
        $property = new \ReflectionProperty($entry, 'createdAt');
        $property->setAccessible(true);
        $property->setValue($entry, new \DateTime($this->now()->modify($createdAt)->format('Y-m-d H:i:s')));

        return $entry;
    }

    /**
     * Un digest dont chaque source est neutre, sauf celles qu'on nomme.
     *
     * Cles reconnues : expedition, craftJob, claimedOrders, unread, house,
     * plots, journal, questsCompleted, questHelper.
     *
     * @param array<string, mixed> $overrides
     */
    private function digestWith(array $overrides = []): PlayerHubDigest
    {
        $expeditionRepository = $this->createMock(PlayerExpeditionRepository::class);
        $expeditionRepository->method('findForPlayer')->willReturn($overrides['expedition'] ?? null);

        $craftJobRepository = $this->createMock(CraftJobRepository::class);
        $craftJobRepository->method('findActiveForPlayer')->willReturn($overrides['craftJob'] ?? null);

        $craftOrderRepository = $this->createMock(CraftOrderRepository::class);
        $craftOrderRepository->method('findClaimedByCrafter')->willReturn($overrides['claimedOrders'] ?? []);

        $privateMessageRepository = $this->createMock(PrivateMessageRepository::class);
        $privateMessageRepository->method('countUnreadForPlayer')->willReturn($overrides['unread'] ?? 0);

        $houseRepository = $this->createMock(PlayerHouseRepository::class);
        $houseRepository->method('findForOwner')->willReturn($overrides['house'] ?? null);

        $gardenPlotRepository = $this->createMock(GardenPlotRepository::class);
        $gardenPlotRepository->method('findForHouse')->willReturn($overrides['plots'] ?? []);

        $journalRepository = $this->createMock(PlayerJournalEntryRepository::class);
        $journalRepository->method('findByPlayer')->willReturn($overrides['journal'] ?? []);

        $questHelper = $overrides['questHelper'] ?? $this->createMock(PlayerQuestHelper::class);
        if (!isset($overrides['questHelper'])) {
            $questHelper->method('isPlayerQuestCompleted')->willReturn($overrides['questsCompleted'] ?? false);
            $questHelper->method('getPlayerQuestProgress')->willReturn(0);
        }

        return new PlayerHubDigest(
            $expeditionRepository,
            $craftJobRepository,
            $craftOrderRepository,
            $privateMessageRepository,
            $houseRepository,
            $gardenPlotRepository,
            $journalRepository,
            $overrides['commissionRepository'] ?? $this->createMock(PlayerWeeklyCommissionRepository::class),
            $questHelper,
        );
    }

    private function digest(): PlayerHubDigest
    {
        return $this->digestWith();
    }
}
