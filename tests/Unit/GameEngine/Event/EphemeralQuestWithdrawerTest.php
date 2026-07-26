<?php

namespace App\Tests\Unit\GameEngine\Event;

use App\Entity\App\GameEvent;
use App\Entity\App\Player;
use App\Entity\App\PlayerQuest;
use App\Entity\Game\Quest;
use App\Event\Game\GameEventCompletedEvent;
use App\GameEngine\Event\EphemeralQuestWithdrawer;
use App\GameEngine\Notification\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class EphemeralQuestWithdrawerTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private NotificationService&MockObject $notificationService;
    private EphemeralQuestWithdrawer $withdrawer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->notificationService = $this->createMock(NotificationService::class);

        $this->withdrawer = new EphemeralQuestWithdrawer(
            $this->entityManager,
            $this->notificationService,
            new NullLogger(),
        );
    }

    private function gameEvent(string $name = 'Festival de la Lune'): GameEvent
    {
        $event = new GameEvent();
        $event->setName($name);

        return $event;
    }

    /**
     * Repartit les appels a getRepository selon l'entite demandee.
     *
     * @param Quest[]       $quests
     * @param PlayerQuest[] $playerQuests
     */
    private function wireRepositories(array $quests, array $playerQuests): void
    {
        $questRepository = $this->createMock(EntityRepository::class);
        $questRepository->method('findBy')->willReturn($quests);

        $playerQuestRepository = $this->createMock(EntityRepository::class);
        $playerQuestRepository->method('findBy')->willReturn($playerQuests);

        $this->entityManager->method('getRepository')
            ->willReturnCallback(static fn (string $class) => Quest::class === $class ? $questRepository : $playerQuestRepository);
    }

    private function playerQuest(string $questName): PlayerQuest
    {
        $quest = new Quest();
        $quest->setName($questName);

        $playerQuest = new PlayerQuest();
        $playerQuest->setPlayer(new Player());
        $playerQuest->setQuest($quest);

        return $playerQuest;
    }

    /**
     * Le defaut que la tache 131 corrige : « ephemere » etait applique a
     * l'entree — la quete devenait invisible et inacceptable — mais jamais a la
     * sortie. Une quete deja acceptee restait dans le journal indefiniment.
     */
    public function testInProgressQuestsAreRemovedWhenTheEventEnds(): void
    {
        $first = $this->playerQuest('Chasse aux lanternes');
        $second = $this->playerQuest('Chasse aux lanternes');
        $this->wireRepositories([new Quest()], [$first, $second]);

        $removed = [];
        $this->entityManager->expects($this->exactly(2))
            ->method('remove')
            ->willReturnCallback(function (object $entity) use (&$removed): void {
                $removed[] = $entity;
            });
        $this->entityManager->expects($this->once())->method('flush');

        $this->assertSame(2, $this->withdrawer->withdraw($this->gameEvent()));
        $this->assertSame([$first, $second], $removed);
    }

    public function testEachAffectedPlayerIsNotified(): void
    {
        $playerQuest = $this->playerQuest('Chasse aux lanternes');
        $this->wireRepositories([new Quest()], [$playerQuest]);

        $this->notificationService->expects($this->once())
            ->method('notify')
            ->with(
                $playerQuest->getPlayer(),
                'quest',
                $this->anything(),
                $this->stringContains('Festival de la Lune'),
                $this->anything(),
            );

        $this->withdrawer->withdraw($this->gameEvent());
    }

    public function testAnEventWithoutQuestsTouchesNothing(): void
    {
        $this->wireRepositories([], []);

        $this->entityManager->expects($this->never())->method('remove');
        $this->entityManager->expects($this->never())->method('flush');
        $this->notificationService->expects($this->never())->method('notify');

        $this->assertSame(0, $this->withdrawer->withdraw($this->gameEvent()));
    }

    public function testAQuestNobodyAcceptedTouchesNothing(): void
    {
        $this->wireRepositories([new Quest()], []);

        $this->entityManager->expects($this->never())->method('remove');
        $this->entityManager->expects($this->never())->method('flush');

        $this->assertSame(0, $this->withdrawer->withdraw($this->gameEvent()));
    }

    public function testTheSubscriberReactsToEventCompletion(): void
    {
        $this->assertArrayHasKey(
            GameEventCompletedEvent::NAME,
            EphemeralQuestWithdrawer::getSubscribedEvents(),
        );

        $this->wireRepositories([new Quest()], [$this->playerQuest('Chasse aux lanternes')]);
        $this->entityManager->expects($this->once())->method('remove');

        $this->withdrawer->onEventCompleted(new GameEventCompletedEvent($this->gameEvent()));
    }
}
