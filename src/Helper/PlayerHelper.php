<?php

namespace App\Helper;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Repository\InventoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Service\ResetInterface;

class PlayerHelper implements ResetInterface
{
    private const SESSION_KEY = '_active_player_id';

    private ?Player $player = null;

    /** @var array<int, Inventory|null> Cache inventaires par type (avec JOIN FETCH) */
    private array $inventoryCache = [];

    public function __construct(
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,
        private readonly RequestStack $requestStack,
        private readonly InventoryRepository $inventoryRepository,
    ) {
    }

    public function getPlayer(): ?Player
    {
        if ($this->player !== null) {
            return $this->player;
        }

        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\User) {
            return null;
        }

        $players = $user->getPlayers();
        if ($players->isEmpty()) {
            return null;
        }

        /** @var EntityRepository $playerRepository */
        $playerRepository = $this->entityManager->getRepository(Player::class);

        $activePlayerId = $this->getActivePlayerId();
        if ($activePlayerId !== null) {
            $player = $playerRepository->find($activePlayerId);
            if ($player instanceof Player && $player->getUser() === $user) {
                $this->player = $player;

                return $this->player;
            }
        }

        $firstPlayer = $players->first() ?: null;
        if ($firstPlayer instanceof Player) {
            $this->player = $playerRepository->find($firstPlayer->getId());
            if ($this->player !== null) {
                $this->setActivePlayer($this->player);
            }
        }

        return $this->player;
    }

    public function setPlayer(Player $player): void
    {
        $this->player = $player;
        $this->setActivePlayer($player);
    }

    public function setActivePlayer(Player $player): void
    {
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_KEY, $player->getId());
        $this->player = $player;
    }

    public function getInventory(): Inventory
    {
        return $this->getBagInventory();
    }

    public function getBagInventory()
    {
        return $this->getInventoryByType(Inventory::TYPE_BAG);
    }

    public function getBankInventory()
    {
        return $this->getInventoryByType(Inventory::TYPE_BANK);
    }

    public function getMateriaInventory()
    {
        return $this->getInventoryByType(Inventory::TYPE_MATERIA);
    }

    /**
     * Charge l'inventaire avec JOIN FETCH (PlayerItem + Item) pour éliminer les requêtes N+1.
     */
    private function getInventoryByType(int $type): Inventory
    {
        if (isset($this->inventoryCache[$type])) {
            return $this->inventoryCache[$type];
        }

        $player = $this->getPlayer();
        $inventory = $this->inventoryRepository->findByPlayerAndTypeWithItems($player, $type);

        if ($inventory === null) {
            $inventory = $this->createInventory($type);
        }

        $this->inventoryCache[$type] = $inventory;

        return $inventory;
    }

    /**
     * @return iterable|PlayerItem[]
     */
    public function getUsableItems()
    {
        foreach ($this->getBagInventory()->getItems() as $item) {
            if ($item->getGenericItem()->getSpell() && $item->getGenericItem()->isObject()) {
                yield $item;
            }
        }
    }

    protected function createInventory(int $type): Inventory
    {
        $inventory = new Inventory();
        $inventory->setSize($this->getInventorySizeByType($type));
        $inventory->setType($type);
        $inventory->setPlayer($this->getPlayer());
        $this->getPlayer()->addInventory($inventory);

        $this->entityManager->persist($inventory);
        $this->entityManager->persist($this->getPlayer());
        $this->entityManager->flush();

        return $inventory;
    }

    public function reset(): void
    {
        $this->player = null;
        $this->inventoryCache = [];
    }

    protected function getInventorySizeByType(int $type)
    {
        return match ($type) {
            Inventory::TYPE_BAG => 100,
            Inventory::TYPE_BANK => 1000,
            Inventory::TYPE_MATERIA => 50,
            default => 0,
        };
    }

    private function getActivePlayerId(): ?int
    {
        $session = $this->requestStack->getSession();

        $id = $session->get(self::SESSION_KEY);

        return $id !== null ? (int) $id : null;
    }
}
