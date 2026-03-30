<?php

namespace App\Helper;

use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Service\ResetInterface;

class PlayerHelper implements ResetInterface
{
    private ?Player $player = null;

    public function __construct(private readonly Security $security, private readonly EntityManagerInterface $entityManager)
    {
    }

    public function getPlayer(): ?Player
    {
        if ($this->player === null) {
            /** @var EntityRepository $playerRepository */
            $playerRepository = $this->entityManager->getRepository(Player::class);
            $user = $this->security->getUser();
            if ($user instanceof \App\Entity\User) {
                $firstPlayer = $user->getPlayers()->first() ?: null;
                if ($firstPlayer instanceof Player) {
                    $this->player = $playerRepository->find($firstPlayer->getId());
                }
            }
        }

        return $this->player;
    }

    public function setPlayer(Player $player): void
    {
        $this->player = $player;
    }

    public function getInventory(): Inventory
    {
        return $this->getBagInventory();
    }

    public function getBagInventory()
    {
        foreach ($this->getPlayer()->getInventories() as $inventory) {
            if ($inventory->isBag()) {
                return $inventory;
            }
        }

        return $this->createInventory(Inventory::TYPE_BAG);
    }

    public function getBankInventory()
    {
        foreach ($this->getPlayer()->getInventories() as $inventory) {
            if ($inventory->isBank()) {
                return $inventory;
            }
        }

        return $this->createInventory(Inventory::TYPE_BANK);
    }

    public function getMateriaInventory()
    {
        foreach ($this->getPlayer()->getInventories() as $inventory) {
            if ($inventory->isMateria()) {
                return $inventory;
            }
        }

        return $this->createInventory(Inventory::TYPE_MATERIA);
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
}
