<?php

namespace App\GameEngine\Zone;

use App\Entity\App\GameEvent;
use App\Entity\App\Parameter;
use App\Entity\App\Player;
use App\Entity\App\PlayerZoneEventParticipation;
use App\Entity\App\Zone;
use App\Entity\App\ZoneBoss;
use App\GameEngine\Generator\PlayerItemGenerator;
use App\Helper\InventoryHelper;
use App\Repository\PlayerZoneEventParticipationRepository;
use App\Repository\ZoneBossRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Boss de zone asynchrone (pivot PBBG, ZON-18).
 *
 * Chaque joueur present depense de l'energie pour lancer un assaut quand il le
 * souhaite ; les degats (bases sur sa statistique d'attaque) s'accumulent sur
 * le pool de PV partage du boss et alimentent sa contribution
 * (`PlayerZoneEventParticipation`, ZON-15). Aucune presence simultanee requise,
 * aucun combat tour par tour. A 0 PV, le loot est distribue a la contribution
 * (generalisation de `WorldBossLootDistributor` au modele zone).
 *
 * Curseurs d'equilibrage (table `parameter`) :
 *  - `zone.energy.cost.assault` : cout d'un assaut (defaut 10).
 *  - `zone.boss.assault_damage_factor` : multiplicateur des degats (defaut 100 = x1.0, entier pour la table).
 */
class ZoneBossService
{
    public const DEFAULT_ASSAULT_COST = 10;
    public const PARAM_ASSAULT_COST = 'zone.energy.cost.assault';
    public const DEFAULT_DAMAGE_FACTOR_PERCENT = 100;
    public const PARAM_DAMAGE_FACTOR = 'zone.boss.assault_damage_factor';

    private const TOP_CONTRIBUTOR_COUNT = 3;
    private const TOP_PROBABILITY_BONUS = 1.5;

    private ?int $assaultCostCache = null;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ZoneBossRepository $bossRepository,
        private readonly PlayerZoneEventParticipationRepository $participationRepository,
        private readonly ActionEnergyManager $actionEnergyManager,
        private readonly PlayerItemGenerator $playerItemGenerator,
        private readonly InventoryHelper $inventoryHelper,
        private readonly HubInterface $hub,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getBossForEvent(GameEvent $event): ?ZoneBoss
    {
        return $this->bossRepository->findOneByGameEvent($event);
    }

    /**
     * Boss actif (non vaincu, dans sa fenetre) rattache a la zone, ou null.
     */
    public function getActiveBossForZone(Zone $zone): ?ZoneBoss
    {
        $now = $this->now();
        /** @var list<ZoneBoss> $bosses */
        $bosses = $this->bossRepository->createQueryBuilder('b')
            ->join('b.gameEvent', 'e')
            ->andWhere('e.zone = :zone')
            ->andWhere('b.defeated = false')
            ->setParameter('zone', $zone)
            ->getQuery()
            ->getResult();

        foreach ($bosses as $boss) {
            if ($boss->getGameEvent()->isActiveAt($now)) {
                return $boss;
            }
        }

        return null;
    }

    /**
     * Lance un assaut du joueur contre le boss de l'evenement.
     *
     * @throws ZoneActionException            si l'assaut est refuse (cle de traduction en message)
     * @throws NotEnoughActionEnergyException si l'energie est insuffisante
     */
    public function assault(Player $player, GameEvent $event): ZoneBossAssaultResult
    {
        $boss = $this->getBossForEvent($event);
        if (null === $boss) {
            throw new ZoneActionException('game.zone.boss.error.no_boss');
        }
        if ($boss->isDefeated()) {
            throw new ZoneActionException('game.zone.boss.error.already_defeated');
        }
        if (!$event->isActiveAt($this->now())) {
            throw new ZoneActionException('game.zone.boss.error.closed');
        }
        if ($player->getCurrentZone() !== $event->getZone()) {
            throw new ZoneActionException('game.zone.boss.error.not_present');
        }

        // L'energie n'est prelevee qu'une fois l'assaut garanti possible.
        $this->actionEnergyManager->spend($player, $this->getAssaultCost(), false);

        $damage = $this->computeDamage($player);
        $dealt = $boss->applyDamage($damage);
        // Lu depuis les PV (et non isDefeated(), narrowe a false par le garde ci-dessus).
        $defeated = $boss->getHpCurrent() <= 0;

        $participation = $this->participationRepository->findOneForPlayerAndEvent($player, $event);
        if (null === $participation) {
            $participation = new PlayerZoneEventParticipation($player, $event);
            $this->entityManager->persist($participation);
        }
        $participation->addContribution($dealt);

        if ($defeated) {
            $this->distributeLoot($boss, $event);
            $this->publishDefeated($boss);
        }

        $this->entityManager->flush();

        return new ZoneBossAssaultResult(
            $dealt,
            $boss->getHpCurrent(),
            $boss->getHpMax(),
            $participation->getContribution(),
            $defeated,
        );
    }

    /**
     * Degats d'un assaut : base sur la statistique d'attaque du joueur
     * (`getHit`), mise a l'echelle par le curseur, avec une variance +/-20 %.
     */
    private function computeDamage(Player $player): int
    {
        $base = max(1, $player->getHit());
        $scaled = $base * ($this->getDamageFactorPercent() / 100.0);
        $variance = $this->roll(41) - 21; // -20..+20 %

        return max(1, (int) round($scaled * (1 + $variance / 100.0)));
    }

    /**
     * Distribue le loot du boss a la contribution : top-3 contributeurs =
     * drops garantis + probabilite boostee ; les autres = probabiliste
     * standard. Les objets sont ajoutes a l'inventaire de chaque joueur.
     */
    private function distributeLoot(ZoneBoss $boss, GameEvent $event): void
    {
        $participations = $this->participationRepository->findByEventOrderedByContribution($event);
        $rank = 0;
        foreach ($participations as $participation) {
            if ($participation->getContribution() <= 0) {
                continue;
            }
            ++$rank;
            $this->grantLoot($boss, $participation->getPlayer(), $rank <= self::TOP_CONTRIBUTOR_COUNT);
        }
    }

    private function grantLoot(ZoneBoss $boss, Player $player, bool $isTop): void
    {
        foreach ($boss->getMonster()->getMonsterItems() as $monsterItem) {
            $item = $monsterItem->getItem();

            if ($monsterItem->isGuaranteed()) {
                if ($isTop) {
                    $this->grantItem($player, (int) $item->getId());
                }

                continue;
            }

            $probability = (int) $monsterItem->getProbability();
            if ($isTop) {
                $probability = (int) round($probability * self::TOP_PROBABILITY_BONUS);
            }
            if ($this->roll(100) <= min(100, $probability)) {
                $this->grantItem($player, (int) $item->getId());
            }
        }

        $this->logger->info('[ZoneBossService] Loot distributed to player {player} (top: {top})', [
            'player' => $player->getId(),
            'top' => $isTop ? 'yes' : 'no',
        ]);
    }

    private function grantItem(Player $player, int $itemId): void
    {
        $playerItem = $this->playerItemGenerator->generateFromItemId($itemId);
        $this->inventoryHelper->addItem($playerItem, false);
    }

    private function publishDefeated(ZoneBoss $boss): void
    {
        try {
            $zone = $boss->getGameEvent()->getZone();
            $topic = 'zone/' . ($zone?->getId() ?? 0) . '/event';
            $update = new Update(
                $topic,
                json_encode([
                    'topic' => $topic,
                    'type' => 'zone_boss_defeated',
                    'boss' => [
                        'eventId' => $boss->getGameEvent()->getId(),
                        'name' => $boss->getMonster()->getName(),
                        'zoneId' => $zone?->getId(),
                    ],
                ], JSON_THROW_ON_ERROR)
            );
            $this->hub->publish($update);
        } catch (\Throwable $e) {
            $this->logger->warning('Failed to publish zone boss defeat via Mercure: {error}', ['error' => $e->getMessage()]);
        }
    }

    public function getAssaultCost(): int
    {
        if (null !== $this->assaultCostCache) {
            return $this->assaultCostCache;
        }

        $parameter = $this->entityManager->getRepository(Parameter::class)
            ->findOneBy(['name' => self::PARAM_ASSAULT_COST]);
        $value = null !== $parameter ? (int) $parameter->getValue() : self::DEFAULT_ASSAULT_COST;

        return $this->assaultCostCache = $value >= 0 ? $value : self::DEFAULT_ASSAULT_COST;
    }

    private function getDamageFactorPercent(): int
    {
        $parameter = $this->entityManager->getRepository(Parameter::class)
            ->findOneBy(['name' => self::PARAM_DAMAGE_FACTOR]);
        $value = null !== $parameter ? (int) $parameter->getValue() : self::DEFAULT_DAMAGE_FACTOR_PERCENT;

        return $value > 0 ? $value : self::DEFAULT_DAMAGE_FACTOR_PERCENT;
    }

    protected function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }

    /**
     * Tirage aleatoire 1..max — surchargeable en test.
     */
    protected function roll(int $max): int
    {
        return random_int(1, max(1, $max));
    }
}
