<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildVault;
use App\Entity\App\GuildVaultLog;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use Doctrine\ORM\EntityManagerInterface;

class GuildVaultManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GuildManager $guildManager,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function deposit(Player $player, PlayerItem $playerItem): void
    {
        $membership = $this->guildManager->getPlayerMembership($player);
        if (!$membership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans une guilde.');
        }

        if (!$membership->getRank()->canDeposit()) {
            throw new \InvalidArgumentException('Vous n\'avez pas la permission de déposer dans le coffre.');
        }

        if ($playerItem->getInventory()?->getPlayer()?->getId() !== $player->getId()) {
            throw new \InvalidArgumentException('Cet objet ne vous appartient pas.');
        }

        if ($playerItem->getGear() > 0) {
            throw new \InvalidArgumentException('Vous ne pouvez pas déposer un objet équipé.');
        }

        if ($playerItem->isBound()) {
            throw new \InvalidArgumentException('Cet objet est lié à votre personnage.');
        }

        $guild = $membership->getGuild();
        $vault = $this->getOrCreateVault($guild);

        if ($vault->isFull()) {
            throw new \InvalidArgumentException('Le coffre de guilde est plein.');
        }

        $inventory = $playerItem->getInventory();
        if ($inventory) {
            $inventory->removeItem($playerItem);
        }
        $playerItem->setInventory(null);

        $vault->addItem($playerItem);

        $log = new GuildVaultLog();
        $log->setGuild($guild);
        $log->setPlayer($player);
        $log->setAction(GuildVaultLog::ACTION_DEPOSIT);
        $log->setItem($playerItem->getGenericItem());
        $log->setQuantity(1);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function withdraw(Player $player, PlayerItem $playerItem): void
    {
        $membership = $this->guildManager->getPlayerMembership($player);
        if (!$membership) {
            throw new \InvalidArgumentException('Vous n\'êtes pas dans une guilde.');
        }

        if (!$membership->getRank()->canWithdraw()) {
            throw new \InvalidArgumentException('Vous n\'avez pas la permission de retirer du coffre.');
        }

        $guild = $membership->getGuild();
        $vault = $guild->getVault();

        if (!$vault || !$vault->getItems()->contains($playerItem)) {
            throw new \InvalidArgumentException('Cet objet n\'est pas dans le coffre de guilde.');
        }

        $bag = $this->getPlayerBag($player);
        if (!$bag) {
            throw new \InvalidArgumentException('Inventaire introuvable.');
        }

        if ($bag->getOccupiedSpace() >= $bag->getSize()) {
            throw new \InvalidArgumentException('Votre inventaire est plein.');
        }

        $vault->removeItem($playerItem);
        $playerItem->setInventory($bag);
        $bag->addItem($playerItem);

        $log = new GuildVaultLog();
        $log->setGuild($guild);
        $log->setPlayer($player);
        $log->setAction(GuildVaultLog::ACTION_WITHDRAW);
        $log->setItem($playerItem->getGenericItem());
        $log->setQuantity(1);

        $this->entityManager->persist($log);
        $this->entityManager->flush();
    }

    public function getOrCreateVault(Guild $guild): GuildVault
    {
        $vault = $guild->getVault();
        if ($vault) {
            return $vault;
        }

        $vault = new GuildVault();
        $vault->setGuild($guild);
        $vault->setCreatedAt(new \DateTime());
        $vault->setUpdatedAt(new \DateTime());

        $guild->setVault($vault);
        $this->entityManager->persist($vault);
        $this->entityManager->flush();

        return $vault;
    }

    /**
     * @return GuildVaultLog[]
     */
    public function getRecentLogs(Guild $guild, int $limit = 20): array
    {
        return $this->entityManager->getRepository(GuildVaultLog::class)->findBy(
            ['guild' => $guild],
            ['createdAt' => 'DESC'],
            $limit,
        );
    }

    private function getPlayerBag(Player $player): ?Inventory
    {
        foreach ($player->getInventories() as $inventory) {
            if ($inventory->isBag()) {
                return $inventory;
            }
        }

        return null;
    }
}
