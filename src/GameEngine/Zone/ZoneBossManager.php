<?php

namespace App\GameEngine\Zone;

use App\Entity\App\GameEvent;
use App\Entity\App\ZoneBoss;
use App\Entity\Game\Monster;
use App\Event\Game\GameEventActivatedEvent;
use App\Repository\ZoneBossRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Cree un boss de zone asynchrone (`ZoneBoss`, ZON-18) quand un evenement de
 * zone porteur d'un boss s'active.
 *
 * Un GameEvent porte un boss de zone s'il est rattache a une zone (ZON-15) et
 * declare, dans ses `parameters`, un `monster_slug` et un pool de PV `boss_hp`
 * (defaut : les PV du monstre). Idempotent : ne recree pas un boss existant.
 */
class ZoneBossManager implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ZoneBossRepository $bossRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            GameEventActivatedEvent::NAME => 'onGameEventActivated',
        ];
    }

    public function onGameEventActivated(GameEventActivatedEvent $event): void
    {
        $gameEvent = $event->getGameEvent();
        if (null === $gameEvent->getZone()) {
            return;
        }

        $params = $gameEvent->getParameters() ?? [];
        $monsterSlug = $params['monster_slug'] ?? null;
        if (!\is_string($monsterSlug) || '' === $monsterSlug) {
            return;
        }

        if (null !== $this->bossRepository->findOneByGameEvent($gameEvent)) {
            return; // deja cree (idempotent)
        }

        $monster = $this->entityManager->getRepository(Monster::class)->findOneBy(['slug' => $monsterSlug]);
        if (null === $monster) {
            $this->logger->warning('[ZoneBossManager] Monster "{slug}" not found for zone boss event "{event}"', [
                'slug' => $monsterSlug,
                'event' => $gameEvent->getName(),
            ]);

            return;
        }

        $hp = isset($params['boss_hp']) ? (int) $params['boss_hp'] : (int) $monster->getLife();
        $boss = new ZoneBoss($gameEvent, $monster, max(1, $hp));
        $this->entityManager->persist($boss);
        $this->entityManager->flush();

        $this->logger->info('[ZoneBossManager] Spawned zone boss "{name}" ({hp} HP) on event "{event}"', [
            'name' => $monster->getName(),
            'hp' => $boss->getHpMax(),
            'event' => $gameEvent->getName(),
        ]);
    }
}
