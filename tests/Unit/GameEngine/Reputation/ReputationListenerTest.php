<?php

namespace App\Tests\Unit\GameEngine\Reputation;

use App\Entity\App\Player;
use App\Entity\Game\Faction;
use App\Entity\Game\Quest;
use App\Event\Fight\MobDeadEvent;
use App\Event\Game\QuestCompletedEvent;
use App\GameEngine\Reputation\ReputationListener;
use App\GameEngine\Reputation\ReputationManager;
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
        );
    }

    public function testSubscribedEvents(): void
    {
        $events = ReputationListener::getSubscribedEvents();

        $this->assertArrayHasKey(MobDeadEvent::NAME, $events);
        $this->assertArrayHasKey(QuestCompletedEvent::NAME, $events);
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
}
