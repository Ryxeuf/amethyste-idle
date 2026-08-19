<?php

namespace App\GameEngine\Guild;

use App\Entity\App\Guild;
use App\Entity\App\GuildVault;
use App\Entity\App\GuildVaultLog;
use App\Entity\App\Inventory;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\GameEngine\GameMaster\GameMasterPolicy;
use Doctrine\ORM\EntityManagerInterface;

class GuildVaultManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly GuildManager $guildManager,
        private readonly GameMasterPolicy $gameMasterPolicy,
    ) {
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function deposit(Player $player, PlayerItem $playerItem): void
    {
        // Le coffre de guilde deplace de la valeur entre joueurs : ferme au MJ,
        // dans les deux sens.
        $this->gameMasterPolicy->assertMayTrade($player);

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

        // FAC-10 : « peut-il circuler ? » se demande **une fois**, et
        // `PlayerItem::isExchangeable()` est cet endroit. Le coffre reecrivait
        // le predicat en deux conditions separees — les memes deux, dans le
        // meme ordre —, et *une regle recopiee derive de son original en
        // silence*. Les deux messages restent distincts : ce qui a de la valeur
        // ici, c'est de dire **laquelle** des deux raisons s'applique.
        if (!$playerItem->isExchangeable()) {
            throw new \InvalidArgumentException($playerItem->getGear() > 0 ? 'Vous ne pouvez pas déposer un objet équipé.' : 'Cet objet est lié à votre personnage.');
        }

        // FAC-07 : le coffre est un canal entre joueurs — une contrefaçon n'y
        // entre jamais, identifiée ou non. Un joueur ne trompe pas sa guilde.
        if ($playerItem->isCounterfeit()) {
            throw new \InvalidArgumentException('Une contrefaçon ne passe pas dans le coffre de guilde.');
        }

        $guild = $membership->getGuild();
        $vault = $this->getOrCreateVault($guild);

        if ($vault->isFull()) {
            throw new \InvalidArgumentException('Le coffre de guilde est plein.');
        }

        $playerItem->getInventory()->removeItem($playerItem);
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
        // Le coffre de guilde deplace de la valeur entre joueurs : ferme au MJ,
        // dans les deux sens.
        $this->gameMasterPolicy->assertMayTrade($player);

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

        // Liaison a l'obtention (ECO-01), comme le fait `InventoryHelper::addItem()`.
        //
        // Aucun objet lie a l'obtention ne devrait pouvoir se trouver dans un
        // coffre : `deposit()` refuse ce qui est deja lie, et un tel objet l'est
        // des son entree en inventaire. La regle repose donc entierement sur la
        // garde du depot — un objet dont le type passe a « lie a l'obtention »
        // alors qu'il dort deja dans un coffre ressortirait libre.
        //
        // Le coffre ne peut pas passer par `InventoryHelper` : celui-ci ecrit
        // dans le sac du joueur **de la session**, la ou le retrait resout le
        // sac du joueur qu'on lui donne. La regle est donc reappliquee ici.
        if ($playerItem->getGenericItem()->isBoundOnPickup() && !$playerItem->isBound()) {
            $playerItem->setBoundToPlayerId($player->getId());
        }

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
