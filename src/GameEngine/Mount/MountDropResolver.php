<?php

namespace App\GameEngine\Mount;

use App\Entity\App\Mob;
use App\Entity\App\Player;
use App\Entity\App\PlayerMount;
use App\Event\Fight\MobDeadEvent;
use App\Repository\MountRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Rolls for mount drops when a non-summoned mob dies. Each candidate mount
 * (matched by `dropMonster`) rolls once against `dropProbability` (0-100).
 * Successful rolls call `MountAcquisitionService::grantMount(SOURCE_DROP)`
 * for a single random non-dead participant of the fight.
 */
class MountDropResolver implements EventSubscriberInterface
{
    public function __construct(
        private readonly MountRepository $mountRepository,
        private readonly MountAcquisitionService $mountAcquisitionService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [MobDeadEvent::NAME => 'onMobDead'];
    }

    public function onMobDead(MobDeadEvent $event): void
    {
        $mob = $event->getMob();
        if ($mob->isSummoned()) {
            return;
        }

        $candidates = $this->mountRepository->findEnabledByDropMonster($mob->getMonster());
        if ($candidates === []) {
            return;
        }

        $beneficiary = $this->pickBeneficiary($mob);
        if ($beneficiary === null) {
            return;
        }

        foreach ($candidates as $mount) {
            $probability = $mount->getDropProbability();
            if ($probability <= 0 || random_int(1, 100) > $probability) {
                continue;
            }

            try {
                $this->mountAcquisitionService->grantMount($beneficiary, $mount, PlayerMount::SOURCE_DROP);
            } catch (MountAlreadyOwnedException|\DomainException) {
                // Idempotent (already owned) or defensive (mount disabled) — ignore.
            }
        }
    }

    private function pickBeneficiary(Mob $mob): ?Player
    {
        $fight = $mob->getFight();
        if ($fight === null) {
            return null;
        }

        $alive = [];
        foreach ($fight->getPlayers() as $player) {
            if (!$player->isDead()) {
                $alive[] = $player;
            }
        }

        if ($alive === []) {
            return null;
        }

        return $alive[random_int(0, count($alive) - 1)];
    }
}
