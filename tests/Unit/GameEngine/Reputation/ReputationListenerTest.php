<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\AuctionListing;
use App\Entity\App\AuctionTransaction;
use App\Entity\App\Fight;
use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\Game\Faction;
use App\Entity\Game\Monster;
use App\Entity\Game\Quest;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\AuctionSaleEvent;
use App\Event\Game\QuestCompletedEvent;
use App\GameEngine\Reputation\GestureReputationCatalog;
use App\GameEngine\Reputation\ReputationListener;
use App\GameEngine\Reputation\ReputationManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ReputationListenerTest extends TestCase
{
    private ReputationManager&MockObject $reputationManager;
    private EntityManagerInterface&MockObject $em;
    private EntityRepository&MockObject $factionRepository;
    private ReputationListener $listener;

    protected function setUp(): void
    {
        $this->reputationManager = $this->createMock(ReputationManager::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->factionRepository = $this->createMock(EntityRepository::class);

        $this->em->method('getRepository')
            ->with(Faction::class)
            ->willReturn($this->factionRepository);

        $this->listener = new ReputationListener(
            $this->reputationManager,
            $this->em,
            new GestureReputationCatalog(\dirname(__DIR__, 4)),
        );
    }

    public function testSubscribedEvents(): void
    {
        $events = ReputationListener::getSubscribedEvents();

        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertArrayHasKey(QuestCompletedEvent::NAME, $events);
        $this->assertArrayHasKey(AuctionSaleEvent::NAME, $events);
    }

    /**
     * FAC-02 : abattre un mort-vivant est le geste de l'Ordre. Le monstre n'a
     * pas de faction propre — c'est la liste declarative du catalogue qui le
     * classe, et le routage `undead_kill` qui dit qui il nourrit. Le montant
     * suit le palier, comme tout kill.
     */
    public function testUndeadKillRoutesToTheKnightsGesture(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);
        $mob = $this->createMobWithFight([$player], 'skeleton', 1, null);

        $this->reputationManager->method('getReputationAmount')->with(1)->willReturn(10);
        $this->reputationManager->expects($this->once())
            ->method('grantGestureReputation')
            ->with($player, 'undead_kill', 10);
        $this->reputationManager->expects($this->never())->method('grantCappedReputation');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    /**
     * Un monstre sans faction et hors de la liste des morts-vivants ne
     * nourrit personne : le tout-venant n'est le geste d'aucune maison.
     */
    public function testAFactionlessLivingMonsterFeedsNoOne(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);
        $mob = $this->createMobWithFight([$player], 'slime', 1, null);

        $this->reputationManager->expects($this->never())->method('grantGestureReputation');
        $this->reputationManager->expects($this->never())->method('grantCappedReputation');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    /**
     * FAC-02 : un kill est un geste — il passe par le chemin plafonne, jamais
     * par `addReputation()` (reserve aux quetes, qu'on ne refait pas).
     */
    public function testAFactionMonsterKillGoesThroughTheCappedPath(): void
    {
        $player = $this->createMock(Player::class);
        $player->method('isDead')->willReturn(false);
        $faction = $this->createFaction('mages');
        $mob = $this->createMobWithFight([$player], 'renegade', 3, $faction);

        $this->reputationManager->method('getReputationAmount')->with(3)->willReturn(25);
        $this->reputationManager->expects($this->once())
            ->method('grantCappedReputation')
            ->with($player, $faction, 25);
        $this->reputationManager->expects($this->never())->method('addReputation');

        $this->listener->onMobDead(new MobDeadEvent($mob));
    }

    /**
     * FAC-02 : la vente conclue a l'hotel des ventes nourrit les Marchands,
     * cote vendeur. Le montant vient de la route, pas de l'appelant.
     */
    public function testAnAuctionSaleFeedsTheMerchantsThroughItsRoute(): void
    {
        $seller = $this->createMock(Player::class);
        $listing = $this->createMock(AuctionListing::class);
        $listing->method('getSeller')->willReturn($seller);
        $transaction = $this->createMock(AuctionTransaction::class);

        $this->reputationManager->expects($this->once())
            ->method('grantGestureReputation')
            ->with($seller, 'auction_sale');

        $this->listener->onAuctionSale(new AuctionSaleEvent($listing, $transaction));
    }

    public function testQuestCompletedAppliesBaseReputation(): void
    {
        $player = $this->createMock(Player::class);
        $faction = $this->createFaction('chevaliers');

        $quest = new Quest();
        $quest->setName('Test');
        $quest->setDescription('Desc');
        $quest->setRewards([
            'reputation' => [
                ['faction_slug' => 'chevaliers', 'amount' => 300],
            ],
        ]);

        $this->factionRepository->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => $criteria['slug'] === 'chevaliers' ? $faction : null);

        $this->reputationManager->expects($this->once())
            ->method('addReputation')
            ->with($player, $faction, 300);

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest));
    }

    public function testQuestCompletedWithoutChoiceIgnoresChoiceOutcomeReputation(): void
    {
        $player = $this->createMock(Player::class);

        $quest = new Quest();
        $quest->setName('Choice Test');
        $quest->setDescription('Desc');
        $quest->setRewards([]);
        $quest->setChoiceOutcome([
            [
                'key' => 'help_guard',
                'label' => 'Aider le garde',
                'bonusRewards' => [
                    'reputation' => [
                        ['faction_slug' => 'chevaliers', 'amount' => 200],
                    ],
                ],
            ],
        ]);

        $this->reputationManager->expects($this->never())->method('addReputation');

        // Choice not made (choiceMade = null) -> no reputation applied.
        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest));
    }

    public function testQuestCompletedAppliesChosenBranchReputationPositiveAndNegative(): void
    {
        $player = $this->createMock(Player::class);
        $chevaliers = $this->createFaction('chevaliers');
        $ombres = $this->createFaction('ombres');

        $quest = new Quest();
        $quest->setName('Allegiance');
        $quest->setDescription('Desc');
        $quest->setRewards([]);
        $quest->setChoiceOutcome([
            [
                'key' => 'side_knights',
                'label' => 'Rejoindre les Chevaliers',
                'bonusRewards' => [
                    'reputation' => [
                        ['faction_slug' => 'chevaliers', 'amount' => 250],
                        ['faction_slug' => 'ombres', 'amount' => -100],
                    ],
                ],
            ],
            [
                'key' => 'side_shadows',
                'label' => 'Rejoindre les Ombres',
                'bonusRewards' => [
                    'reputation' => [
                        ['faction_slug' => 'ombres', 'amount' => 250],
                        ['faction_slug' => 'chevaliers', 'amount' => -100],
                    ],
                ],
            ],
        ]);

        $factions = ['chevaliers' => $chevaliers, 'ombres' => $ombres];
        $this->factionRepository->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => $factions[$criteria['slug']] ?? null);

        $calls = [];
        $this->reputationManager->expects($this->exactly(2))
            ->method('addReputation')
            ->willReturnCallback(function ($p, $f, $amount) use (&$calls) {
                $calls[] = [$f->getSlug(), $amount];

                return $this->createMock(\App\Entity\App\PlayerFaction::class);
            });

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest, 'side_knights'));

        $this->assertSame([
            ['chevaliers', 250],
            ['ombres', -100],
        ], $calls);
    }

    public function testQuestCompletedCombinesBaseAndChoiceReputation(): void
    {
        $player = $this->createMock(Player::class);
        $chevaliers = $this->createFaction('chevaliers');
        $marchands = $this->createFaction('marchands');

        $quest = new Quest();
        $quest->setName('Mixed');
        $quest->setDescription('Desc');
        $quest->setRewards([
            'reputation' => [
                ['faction_slug' => 'marchands', 'amount' => 50],
            ],
        ]);
        $quest->setChoiceOutcome([
            [
                'key' => 'noble',
                'label' => 'Agir noblement',
                'bonusRewards' => [
                    'reputation' => [
                        ['faction_slug' => 'chevaliers', 'amount' => 150],
                    ],
                ],
            ],
        ]);

        $factions = ['marchands' => $marchands, 'chevaliers' => $chevaliers];
        $this->factionRepository->method('findOneBy')
            ->willReturnCallback(fn (array $criteria) => $factions[$criteria['slug']] ?? null);

        $calls = [];
        $this->reputationManager->expects($this->exactly(2))
            ->method('addReputation')
            ->willReturnCallback(function ($p, $f, $amount) use (&$calls) {
                $calls[] = [$f->getSlug(), $amount];

                return $this->createMock(\App\Entity\App\PlayerFaction::class);
            });

        $this->listener->onQuestCompleted(new QuestCompletedEvent($player, $quest, 'noble'));

        $this->assertSame([
            ['marchands', 50],
            ['chevaliers', 150],
        ], $calls);
    }

    private function createFaction(string $slug): Faction
    {
        $faction = new Faction();
        $faction->setSlug($slug);
        $faction->setName(ucfirst($slug));

        return $faction;
    }

    /**
     * @param list<Player&MockObject> $players
     */
    private function createMobWithFight(array $players, string $monsterSlug, int $tier, ?Faction $faction): Mob&MockObject
    {
        $monster = $this->createMock(Monster::class);
        $monster->method('getSlug')->willReturn($monsterSlug);
        $monster->method('getTier')->willReturn($tier);
        $monster->method('getFaction')->willReturn($faction);

        $fight = $this->createMock(Fight::class);
        $fight->method('getPlayers')->willReturn(new ArrayCollection($players));

        $mob = $this->createMock(Mob::class);
        $mob->method('getFight')->willReturn($fight);
        $mob->method('getMonster')->willReturn($monster);
        $mob->method('isSummoned')->willReturn(false);

        return $mob;
    }
}
