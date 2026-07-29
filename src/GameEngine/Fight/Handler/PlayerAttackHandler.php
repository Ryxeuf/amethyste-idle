<?php

namespace App\GameEngine\Fight\Handler;

use App\Entity\App\Fight;
use App\Entity\App\Player;
use App\Entity\App\PlayerItem;
use App\Entity\CharacterInterface;
use App\Event\Fight\PlayerAttackHitEvent;
use App\Event\Fight\PlayerAttackMissEvent;
use App\GameEngine\Fight\BareHandsAttack;
use App\GameEngine\Item\ItemUsageResolver;
use App\Helper\FightHelper;
use App\Helper\GearHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class PlayerAttackHandler extends AbstractPayerItemHandler
{
    public function __construct(private readonly GearHelper $gearHelper, EntityManagerInterface $entityManager, FightHelper $fightHelper, private readonly EventDispatcherInterface $eventDispatcher, ItemUsageResolver $itemUsageResolver, private readonly BareHandsAttack $bareHandsAttack)
    {
        parent::__construct($entityManager, $fightHelper, $itemUsageResolver);
    }

    public function supports(Fight $fight, string $context): bool
    {
        return PlayerActionHandlerInterface::ACTION_ATTACK === $context;
    }

    /**
     * ONB-20a : sans arme, on frappe a mains nues.
     *
     * Avant ce jalon, `getItem()` levait et l'action echouait : un personnage
     * sans arme n'avait aucun chemin de combat. L'attaque de base est desormais
     * toujours disponible — c'est la condition pour qu'ONB-08 puisse faire de
     * l'acces a un arbre, donc au port d'une arme, un acte a poser sans
     * enfermer personne.
     */
    public function applyAction(Fight $fight, Player $player): bool
    {
        if ($this->hasUsableWeapon()) {
            return parent::applyAction($fight, $player);
        }

        if (!$target = $this->fightHelper->getTarget($fight)) {
            return false;
        }

        $this->bareHandsAttack->strike($player, $target);

        return true;
    }

    protected function getItem(Fight $fight): PlayerItem
    {
        $weapon = $this->gearHelper->getWeaponGear();
        if (!$weapon || !$weapon->getGenericItem()->getSpell()) {
            throw new EntityNotFoundException('Player attack impossible');
        }

        return $weapon;
    }

    public function resolveItem(PlayerItem $item, Player $player, CharacterInterface $target): bool
    {
        if ($hit = parent::resolveItem($item, $player, $target)) {
            $this->eventDispatcher->dispatch(new PlayerAttackHitEvent($item->getGenericItem()->getName()), PlayerAttackHitEvent::NAME);
        } else {
            $this->eventDispatcher->dispatch(new PlayerAttackMissEvent($item->getGenericItem()->getName()), PlayerAttackMissEvent::NAME);
        }

        return $hit;
    }

    /**
     * Une arme sans sort ne frappe pas : elle vaut une main vide.
     */
    private function hasUsableWeapon(): bool
    {
        $weapon = $this->gearHelper->getWeaponGear();

        return $weapon !== null && $weapon->getGenericItem()->getSpell() !== null;
    }
}
