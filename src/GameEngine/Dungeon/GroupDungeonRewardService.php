<?php

namespace App\GameEngine\Dungeon;

use App\Entity\App\GroupDungeonClear;
use App\Entity\App\GroupDungeonRun;
use App\Entity\App\Inventory;
use App\Entity\App\Parameter;
use App\Entity\App\PlayerItem;
use App\Event\Game\GroupDungeonCompletedEvent;
use App\GameEngine\Materia\MateriaLootTable;
use App\Repository\GroupDungeonClearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Recompenses & lockouts des donjons de groupe (pivot PBBG, ZON-20).
 *
 * A la reussite d'un run, chaque membre recoit des gils. Plutot qu'un blocage
 * sec (lockout dur), on applique une recompense **decroissante** : chaque
 * reussite supplementaire du meme donjon dans la fenetre glissante
 * (`zone.dungeon.lockout.window_hours`) reduit la recompense d'un facteur
 * `zone.dungeon.lockout.decay`, borne par un plancher
 * `zone.dungeon.lockout.min_factor`. Le joueur peut toujours rejouer (variete
 * de contenu, cooperation), mais le farm repetitif rapporte de moins en moins
 * — protection de l'economie.
 *
 * Curseurs (table `parameter`) :
 *  - `zone.dungeon.reward.base_gils` : gils de base par membre (defaut 150).
 *  - `zone.dungeon.lockout.window_hours` : fenetre glissante en heures (defaut 24).
 *  - `zone.dungeon.lockout.decay` : facteur multiplicatif par reussite recente (defaut 0.5).
 *  - `zone.dungeon.lockout.min_factor` : plancher de recompense (defaut 0.25).
 */
class GroupDungeonRewardService
{
    public const DEFAULT_BASE_GILS = 150;
    public const PARAM_BASE_GILS = 'zone.dungeon.reward.base_gils';
    public const DEFAULT_WINDOW_HOURS = 24;
    public const PARAM_WINDOW_HOURS = 'zone.dungeon.lockout.window_hours';
    public const DEFAULT_DECAY = 0.5;
    public const PARAM_DECAY = 'zone.dungeon.lockout.decay';
    public const DEFAULT_MIN_FACTOR = 0.25;
    public const PARAM_MIN_FACTOR = 'zone.dungeon.lockout.min_factor';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GroupDungeonClearRepository $clearRepository,
        private readonly MateriaLootTable $materiaLootTable,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Recompense chaque membre d'un run complete et trace la reussite.
     * Idempotence : appele au seul instant ou le run passe `completed`.
     */
    public function award(GroupDungeonRun $run): void
    {
        $dungeon = $run->getDungeon();
        $now = $this->now();
        $since = $now->modify(sprintf('-%d hours', $this->getWindowHours()));
        $base = $this->getBaseGils();
        $decay = $this->getDecay();
        $minFactor = $this->getMinFactor();

        foreach ($run->getMemberPlayers() as $player) {
            $recentClears = $this->clearRepository->countRecentClears($player, $dungeon, $since);
            $factor = max($minFactor, $decay ** $recentClears);
            $gils = (int) round($base * $factor);

            if ($gils > 0) {
                $player->addGils($gils);
            }

            // DON-04 : la table de materia est indexee sur le palier de la
            // zone du donjon (T1 → m2 … T4 → m4-m5, GAME_MATERIA §4.3), et la
            // decroissance de ZON-20 s'etend aux objets — la chance de drop
            // suit decay^n **sans plancher** : les gils gardent leur plancher
            // (prix de la participation), le sommet du catalogue n'en a pas.
            if ($this->materiaRoll() < $this->materiaDropChance($recentClears)) {
                $materia = $this->materiaLootTable->dungeonPick($this->dungeonTier($run));
                if (null !== $materia) {
                    foreach ($player->getInventories() as $inventory) {
                        if ($inventory->getType() === Inventory::TYPE_MATERIA) {
                            $playerItem = new PlayerItem();
                            $playerItem->setGenericItem($materia);
                            $playerItem->setNbUsages($materia->getNbUsages());
                            $playerItem->setInventory($inventory);

                            // Liaison a l'obtention (ECO-01), comme `InventoryHelper::addItem()` —
                            // qui ecrit dans le sac du joueur de session, pas celui qu'on recompense.
                            if ($materia->isBoundOnPickup() && !$playerItem->isBound()) {
                                $playerItem->setBoundToPlayerId($player->getId());
                            }

                            $this->entityManager->persist($playerItem);
                            break;
                        }
                    }
                }
            }

            $this->entityManager->persist(new GroupDungeonClear($player, $dungeon, $run, $gils, $now));

            // DON-01b : la voie unique porte le suivi (succes, journal) que
            // l'ancien chemin solo emettait — une fois par membre.
            $this->eventDispatcher->dispatch(new GroupDungeonCompletedEvent($player, $run), GroupDungeonCompletedEvent::NAME);
        }
    }

    /**
     * La chance (sur 100) qu'une materia tombe, selon les reussites recentes
     * (DON-04). `decay^n` sans plancher : 100 % sur une reussite fraiche,
     * puis 50, 25… — le plancher des gils est le prix de la participation,
     * le sommet du catalogue n'en a pas.
     */
    public function materiaDropChance(int $recentClears): int
    {
        return (int) round(100 * ($this->getDecay() ** max(0, $recentClears)));
    }

    /**
     * Le palier du donjon : celui de sa zone, plancher T1 (meme lecture que
     * les rencontres de DON-03).
     */
    private function dungeonTier(GroupDungeonRun $run): int
    {
        $tier = $run->getDungeon()->getZone()?->getTier() ?? $run->getZone()?->getTier() ?? 1;

        return max(1, min(4, $tier));
    }

    /**
     * Jet 0-99 du drop de materia — surchargeable en test.
     */
    protected function materiaRoll(): int
    {
        return random_int(0, 99);
    }

    public function getBaseGils(): int
    {
        $value = $this->readInt(self::PARAM_BASE_GILS, self::DEFAULT_BASE_GILS);

        return $value >= 0 ? $value : self::DEFAULT_BASE_GILS;
    }

    public function getWindowHours(): int
    {
        $value = $this->readInt(self::PARAM_WINDOW_HOURS, self::DEFAULT_WINDOW_HOURS);

        return $value > 0 ? $value : self::DEFAULT_WINDOW_HOURS;
    }

    public function getDecay(): float
    {
        $value = $this->readFloat(self::PARAM_DECAY, self::DEFAULT_DECAY);

        return $value > 0 && $value <= 1 ? $value : self::DEFAULT_DECAY;
    }

    public function getMinFactor(): float
    {
        $value = $this->readFloat(self::PARAM_MIN_FACTOR, self::DEFAULT_MIN_FACTOR);

        return $value >= 0 && $value <= 1 ? $value : self::DEFAULT_MIN_FACTOR;
    }

    private function readInt(string $name, int $default): int
    {
        $parameter = $this->entityManager->getRepository(Parameter::class)->findOneBy(['name' => $name]);

        return null !== $parameter ? (int) $parameter->getValue() : $default;
    }

    private function readFloat(string $name, float $default): float
    {
        $parameter = $this->entityManager->getRepository(Parameter::class)->findOneBy(['name' => $name]);

        return null !== $parameter ? (float) $parameter->getValue() : $default;
    }

    protected function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable();
    }
}
